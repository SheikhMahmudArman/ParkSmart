<?php

use App\Services\RagService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('parking_lots', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('location');
        $table->decimal('hourly_rate', 10, 2);
        $table->string('type');
        $table->json('features')->nullable();
    });

    Schema::create('parking_spaces', function (Blueprint $table) {
        $table->id();
        $table->foreignId('parking_lot_id');
        $table->string('status');
    });

    Schema::create('reservations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('space_id');
        $table->date('reservation_date');
        $table->time('start_time');
        $table->time('end_time');
        $table->string('status');
    });

    Schema::create('chat_knowledge_vectors', function (Blueprint $table) {
        $table->id();
        $table->string('source', 191);
        $table->unsignedInteger('chunk_index');
        $table->char('content_hash', 64)->unique();
        $table->longText('content');
        $table->json('embedding');
        $table->timestamps();
    });

    DB::table('parking_lots')->insert([
        'name' => 'Central Garage',
        'location' => 'Dhaka',
        'hourly_rate' => 80,
        'type' => 'Covered',
        'features' => json_encode(['CCTV']),
    ]);
    DB::table('parking_spaces')->insert([
        ['parking_lot_id' => 1, 'status' => 'Available'],
        ['parking_lot_id' => 1, 'status' => 'Occupied'],
        ['parking_lot_id' => 1, 'status' => 'Available'],
    ]);

    DB::statement("INSERT INTO reservations (space_id, reservation_date, start_time, end_time, status) VALUES (1, date('now'), time('now', '-5 minutes'), time('now', '+5 minutes'), 'Active')");
    DB::statement(<<<'SQL'
        CREATE VIEW v_lot_availability AS
        SELECT parking_lots.name, parking_lots.location, parking_lots.hourly_rate,
            parking_lots.type, parking_lots.features,
            COUNT(parking_spaces.id) AS total_spots,
            COALESCE(SUM(CASE WHEN parking_spaces.status = 'Available'
                AND NOT EXISTS (
                    SELECT 1 FROM reservations
                    WHERE reservations.space_id = parking_spaces.id
                        AND reservations.status IN ('Pending', 'Confirmed', 'Active')
                        AND datetime(reservations.reservation_date || ' ' || reservations.start_time) <= datetime('now')
                        AND datetime(reservations.reservation_date || ' ' || reservations.end_time) > datetime('now')
                ) THEN 1 ELSE 0 END), 0) AS available_spots
        FROM parking_lots
        LEFT JOIN parking_spaces ON parking_spaces.parking_lot_id = parking_lots.id
        GROUP BY parking_lots.id, parking_lots.name, parking_lots.location,
            parking_lots.hourly_rate, parking_lots.type, parking_lots.features
        SQL);
});

test('chat uses local guidance when the LLM key is not configured', function () {
    config(['services.openrouter.key' => null]);

    $this->postJson('/api/chat', ['message' => 'How do I reserve a space?'])
        ->assertOk()
        ->assertJsonPath('fallback', true)
        ->assertJsonPath('fallback_reason', 'missing_api_key')
        ->assertJsonPath('lots.0.name', 'Central Garage')
        ->assertJsonPath('lots.0.available_spaces', 1)
        ->assertJsonPath('lots.0.total_spaces', 3);
});

test('chat retrieves relevant knowledge and sends it with live data to OpenRouter', function () {
    config(['services.openrouter.key' => 'test-key']);
    config(['services.embeddings.top_k' => 1]);
    DB::table('chat_knowledge_vectors')->insert([
        [
            'source' => 'test-guide',
            'chunk_index' => 0,
            'content_hash' => hash('sha256', 'relevant'),
            'content' => 'Each started hour is billed using the selected lot hourly rate.',
            'embedding' => json_encode([1, 0]),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'source' => 'test-guide',
            'chunk_index' => 1,
            'content_hash' => hash('sha256', 'irrelevant'),
            'content' => 'Unrelated account guidance.',
            'embedding' => json_encode([0, 1]),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    Http::fake([
        '*/api/embed' => Http::response(['embeddings' => [[1, 0]]]),
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'The selected lot rate applies.']]],
        ]),
    ]);

    $this->postJson('/api/chat', ['message' => 'How is parking priced?'])
        ->assertOk()
        ->assertJsonPath('answer', 'The selected lot rate applies.');

    Http::assertSent(function ($request) {
        if (! str_ends_with($request->url(), '/chat/completions')) {
            return false;
        }

        $body = json_decode($request->body(), true);
        $context = $body['messages'][0]['content'] ?? '';

        return str_contains($context, 'Each started hour is billed')
            && str_contains($context, 'Central Garage (Dhaka): 1 free of 3 spaces')
            && ! str_contains($context, 'Unrelated account guidance.');
    });
});

test('chat falls back when the local embedding model is unavailable', function () {
    config(['services.openrouter.key' => 'test-key']);
    Http::fake(['*/api/embed' => Http::response([], 503)]);

    $this->postJson('/api/chat', ['message' => 'How is parking priced?'])
        ->assertOk()
        ->assertJsonPath('fallback', true)
        ->assertJsonPath('fallback_reason', 'embedding_unavailable');
});

test('chat falls back when the knowledge base has not been indexed', function () {
    config(['services.openrouter.key' => 'test-key']);
    Http::fake(['*/api/embed' => Http::response(['embeddings' => [[1, 0]]])]);

    $this->postJson('/api/chat', ['message' => 'How is parking priced?'])
        ->assertOk()
        ->assertJsonPath('fallback', true)
        ->assertJsonPath('fallback_reason', 'knowledge_not_indexed');
});

test('knowledge ingestion embeds chunks and replaces vectors for its source', function () {
    config([
        'services.embeddings.chunk_size' => 100,
        'services.embeddings.chunk_overlap' => 20,
    ]);
    Http::fake([
        '*/api/embed' => Http::response(['embeddings' => [[1, 0], [0, 1]]]),
    ]);
    DB::table('chat_knowledge_vectors')->insert([
        'source' => 'test-guide',
        'chunk_index' => 0,
        'content_hash' => hash('sha256', 'old chunk'),
        'content' => 'Old indexed content.',
        'embedding' => json_encode([1, 1]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $count = app(RagService::class)->ingest(
        'test-guide',
        str_repeat('a', 70) . "\n\n" . str_repeat('b', 70)
    );

    expect($count)->toBe(2)
        ->and(DB::table('chat_knowledge_vectors')->where('source', 'test-guide')->count())->toBe(2);
});

test('chat preserves database availability when OpenRouter credits are exhausted', function () {
    config(['services.openrouter.key' => 'test-key']);
    DB::table('chat_knowledge_vectors')->insert([
        'source' => 'test-guide',
        'chunk_index' => 0,
        'content_hash' => hash('sha256', 'parking availability'),
        'content' => 'Search Parking shows live parking availability.',
        'embedding' => json_encode([1, 0]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Http::fake([
        '*/api/embed' => Http::response(['embeddings' => [[1, 0]]]),
        '*/chat/completions' => Http::response([
            'error' => ['message' => 'You have no credits remaining.', 'code' => 'insufficient_quota'],
        ], 429),
    ]);

    $this->postJson('/api/chat', ['message' => 'What parking spaces are available?'])
        ->assertOk()
        ->assertJsonPath('fallback', true)
        ->assertJsonPath('fallback_reason', 'no_api_credits')
        ->assertJsonPath('lots.0.name', 'Central Garage')
        ->assertJsonPath('lots.0.available_spaces', 1)
        ->assertJsonPath('lots.0.total_spaces', 3);
});