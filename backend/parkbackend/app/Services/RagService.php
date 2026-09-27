<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RagService
{
    /** @return list<string> */
    public function retrieve(string $query, ?int $limit = null): array
    {
        $queryEmbedding = $this->embedTexts([$query])[0] ?? null;
        if (! is_array($queryEmbedding) || $queryEmbedding === []) {
            throw new RuntimeException('The embedding model returned no query vector.');
        }

        $matches = [];
        foreach (DB::table('chat_knowledge_vectors')->get(['content', 'embedding']) as $row) {
            $embedding = is_array($row->embedding)
                ? $row->embedding
                : json_decode((string) $row->embedding, true);
            if (! is_array($embedding)) {
                continue;
            }

            $score = $this->cosineSimilarity($queryEmbedding, $embedding);
            if ($score !== null) {
                $matches[] = ['content' => $row->content, 'score' => $score];
            }
        }

        usort($matches, fn (array $left, array $right) => $right['score'] <=> $left['score']);

        return array_column(array_slice($matches, 0, $limit ?? (int) config('services.embeddings.top_k', 4)), 'content');
    }

    public function ingest(string $source, string $text): int
    {
        $chunks = $this->chunkText($text);
        if ($chunks === []) {
            throw new RuntimeException('The knowledge document is empty.');
        }

        $embeddings = $this->embedTexts($chunks);
        if (count($embeddings) !== count($chunks)) {
            throw new RuntimeException('The embedding model returned an unexpected number of vectors.');
        }

        DB::transaction(function () use ($source, $chunks, $embeddings): void {
            DB::table('chat_knowledge_vectors')->where('source', $source)->delete();
            $rows = [];
            foreach ($chunks as $index => $content) {
                $rows[] = [
                    'source' => $source,
                    'chunk_index' => $index,
                    'content_hash' => hash('sha256', $source . "\0" . $content),
                    'content' => $content,
                    'embedding' => json_encode($embeddings[$index], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('chat_knowledge_vectors')->insert($rows);
        });

        return count($chunks);
    }

    /** @param list<string> $texts
     *  @return list<list<float|int>>
     */
    private function embedTexts(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $baseUrl = rtrim((string) config('services.embeddings.base_url'), '/');
        $response = Http::acceptJson()
            ->timeout(90)
            ->post($baseUrl . '/api/embed', [
                'model' => config('services.embeddings.model', 'nomic-embed-text'),
                'input' => array_values($texts),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('The local embedding model returned HTTP ' . $response->status() . '.');
        }

        $vectors = $response->json('embeddings');
        if (! is_array($vectors) && count($texts) === 1) {
            $single = $response->json('embedding');
            $vectors = is_array($single) ? [$single] : null;
        }
        if (! is_array($vectors)) {
            throw new RuntimeException('The local embedding model response did not contain vectors.');
        }

        return $vectors;
    }

    /** @return list<string> */
    private function chunkText(string $text): array
    {
        $size = max(100, (int) config('services.embeddings.chunk_size', 1000));
        $overlap = min(max(0, (int) config('services.embeddings.chunk_overlap', 150)), $size - 1);
        $paragraphs = preg_split('/\R{2,}/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];
        $buffer = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (mb_strlen($paragraph) > $size) {
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                    $buffer = '';
                }
                $step = max(1, $size - $overlap);
                for ($offset = 0, $length = mb_strlen($paragraph); $offset < $length; $offset += $step) {
                    $chunks[] = trim(mb_substr($paragraph, $offset, $size));
                }
                continue;
            }

            $candidate = $buffer === '' ? $paragraph : $buffer . "\n\n" . $paragraph;
            if (mb_strlen($candidate) <= $size) {
                $buffer = $candidate;
                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
                $carry = $overlap > 0 ? mb_substr($buffer, -$overlap) . "\n\n" : '';
                $buffer = mb_strlen($carry . $paragraph) <= $size ? $carry . $paragraph : $paragraph;
            } else {
                $buffer = $paragraph;
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return array_values(array_unique(array_filter(array_map('trim', $chunks))));
    }

    /** @param list<float|int> $left
     *  @param list<float|int> $right
     */
    private function cosineSimilarity(array $left, array $right): ?float
    {
        if (count($left) !== count($right) || $left === []) {
            return null;
        }

        $dot = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;
        foreach ($left as $index => $value) {
            $leftValue = (float) $value;
            $rightValue = (float) $right[$index];
            $dot += $leftValue * $rightValue;
            $leftNorm += $leftValue ** 2;
            $rightNorm += $rightValue ** 2;
        }

        if ($leftNorm === 0.0 || $rightNorm === 0.0) {
            return null;
        }

        return $dot / (sqrt($leftNorm) * sqrt($rightNorm));
    }
}
