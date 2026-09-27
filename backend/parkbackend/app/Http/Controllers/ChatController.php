<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function ask(Request $request)
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

        $key = config('services.openai.key');
        if (!$key) {
            return $this->fallbackResponse($lots, 'missing_api_key');
        }

        $context = <<<'TEXT'
You are ParkSmart Assistant, a helpful customer-service chatbot for a parking company in Dhaka.
Answer only using the ParkSmart information below. If the user asks for private account information, say that you cannot access personal bookings and direct them to the relevant page in the app. Never invent a parking lot, price, space count, policy, transaction, or reservation. Keep answers concise and practical.

Verified ParkSmart information:
- Times are shown in Asia/Dhaka and prices are in BDT.
- Drivers search parking lots, add a vehicle in Profile, and reserve a future date with a start and end time on the same date.
- Each started hour is billed using the selected lot's hourly rate.
- Search Parking shows each lot's location, current free spaces, total spaces, parking type, features, and hourly rate.
- Entry must match the reservation, vehicle, lot, space, and booked time. Staff record entry and exit.
- Leaving after the booked end time can increase the final amount and create an overstay fine.
- Unpaid Pending or Confirmed bookings can be cancelled. Paid bookings require operator handling for refunds or adjustments.
- The current application uses demo payments for coursework; no real card or bank payment is processed.
- Drivers manage reservations, vehicles, notifications, payment history, and fines in their account.
- For immediate lot issues, users should contact on-site staff. For emergencies, follow posted site instructions and contact local emergency services.

Current public parking availability:
TEXT;

        foreach ($lots as $lot) {
            $context .= sprintf(
                "\n- %s (%s): %s free of %s spaces, %s, %s BDT/hour, features: %s",
                $lot->name,
                $lot->location,
                (int) $lot->available_spaces,
                (int) $lot->total_spaces,
                $lot->type,
                $lot->hourly_rate,
                is_string($lot->features) ? $lot->features : json_encode($lot->features ?? [])
            );
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout(30)
                ->post(rtrim(config('services.openai.base_url'), '/') . '/chat/completions', [
                    'model' => config('services.openai.model'),
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

            Log::warning('OpenAI chat request failed; using local fallback.', [
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
