<?php

namespace App\Http\Controllers;

use App\Services\RagService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatController extends Controller
{
    public function ask(Request $request, RagService $rag)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $lots = DB::table('v_lot_availability')
            ->select(
                'name',
                'location',
                'hourly_rate',
                'type',
                'features',
                'total_spots as total_spaces',
                'available_spots as available_spaces'
            )
            ->get();

        $key = config('services.openrouter.key');
        if (! $key) {
            return $this->fallbackResponse($lots, 'missing_api_key');
        }

        try {
            $retrievedChunks = $rag->retrieve($data['message']);
        } catch (ConnectionException|RuntimeException $exception) {
            Log::warning('Local embedding model unavailable for RAG retrieval.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackResponse($lots, 'embedding_unavailable');
        }

        if ($retrievedChunks === []) {
            return $this->fallbackResponse($lots, 'knowledge_not_indexed');
        }

        $availability = $lots->map(fn ($lot) => sprintf(
            '%s (%s): %d free of %d spaces, %s, %s BDT/hour, features: %s',
            $lot->name,
            $lot->location,
            (int) $lot->available_spaces,
            (int) $lot->total_spaces,
            $lot->type,
            $lot->hourly_rate,
            is_string($lot->features) ? $lot->features : json_encode($lot->features ?? [])
        ))->implode("\n");

        $context = "You are ParkSmart Assistant, a helpful customer-service chatbot for a parking company in Dhaka.\n"
            . "Answer using only the retrieved reference material and live parking availability below. If the answer is not supported, say you do not have that information. Never invent a lot, price, space count, policy, transaction, or reservation. Do not disclose private account information; direct users to the relevant page in the app. Keep answers concise and practical. Times are Asia/Dhaka and prices are in BDT.\n\n"
            . "Retrieved reference material:\n"
            . implode("\n\n---\n\n", $retrievedChunks)
            . "\n\nLive public parking availability (query-time database data):\n"
            . ($availability !== '' ? $availability : 'No parking lots are currently listed.');

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout(30)
                ->withHeaders(array_filter([
                    'HTTP-Referer' => config('services.openrouter.site_url'),
                    'X-Title' => config('services.openrouter.site_name'),
                ]))
                ->post(rtrim(config('services.openrouter.base_url'), '/') . '/chat/completions', [
                    'model' => config('services.openrouter.model'),
                    'temperature' => 0.2,
                    'messages' => [
                        ['role' => 'system', 'content' => $context],
                        ['role' => 'user', 'content' => $data['message']],
                    ],
                ]);
        } catch (ConnectionException) {
            return $this->fallbackResponse($lots, 'service_unavailable');
        }

        if ($response->failed()) {
            $errorMessage = strtolower((string) data_get($response->json(), 'error.message'));
            $errorCode = data_get($response->json(), 'error.code');
            $fallbackReason = $errorCode === 'insufficient_quota' || str_contains($errorMessage, 'no credits remaining')
                ? 'no_api_credits'
                : ($response->status() === 429 ? 'rate_limited' : 'provider_error');

            Log::warning('OpenRouter chat request failed; using local fallback.', [
                'status' => $response->status(),
                'fallback_reason' => $fallbackReason,
            ]);

            return $this->fallbackResponse($lots, $fallbackReason);
        }

        $answer = trim((string) data_get($response->json(), 'choices.0.message.content'));
        if ($answer === '') {
            return $this->fallbackResponse($lots, 'empty_response');
        }

        return response()->json(['answer' => $answer]);
    }

    private function fallbackResponse($lots, string $reason)
    {
        return response()->json([
            'fallback' => true,
            'fallback_reason' => $reason,
            'lots' => $lots->map(function ($lot) {
                return [
                    'name' => $lot->name,
                    'location' => $lot->location,
                    'hourly_rate' => $lot->hourly_rate,
                    'type' => $lot->type,
                    'features' => is_string($lot->features)
                        ? json_decode($lot->features, true) ?? []
                        : ($lot->features ?? []),
                    'total_spaces' => (int) $lot->total_spaces,
                    'available_spaces' => (int) $lot->available_spaces,
                ];
            })->values(),
        ]);
    }
}
