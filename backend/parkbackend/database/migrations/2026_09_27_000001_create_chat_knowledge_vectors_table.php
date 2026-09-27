<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_knowledge_vectors', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 191);
            $table->unsignedInteger('chunk_index');
            $table->char('content_hash', 64)->unique();
            $table->longText('content');
            $table->json('embedding');
            $table->timestamps();
            $table->index(['source', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_knowledge_vectors');
    }
};
