<?php

namespace App\Console\Commands;

use App\Services\RagService;
use Illuminate\Console\Command;
use Throwable;

class IngestKnowledgeCommand extends Command
{
    protected $signature = 'rag:ingest {--file= : Knowledge document to index} {--source=parksmart-guide : Stable document source identifier}';

    protected $description = 'Chunk a knowledge document, create local embeddings, and store them for RAG retrieval';

    public function handle(RagService $rag): int
    {
        $path = $this->option('file') ?: resource_path('knowledge/parksmart.md');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Knowledge document is missing or unreadable: {$path}");

            return self::FAILURE;
        }

        try {
            $count = $rag->ingest((string) $this->option('source'), (string) file_get_contents($path));
        } catch (Throwable $exception) {
            $this->error('Knowledge indexing failed: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Indexed {$count} chunks from {$path} using the configured local embedding model.");

        return self::SUCCESS;
    }
}
