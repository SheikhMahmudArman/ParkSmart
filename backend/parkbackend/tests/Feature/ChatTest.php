<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
    config(['services.openai.key' => null]);

    $this->postJson('/api/chat', ['message' => 'How do I reserve a space?'])
        ->assertOk()
        ->assertJsonPath('fallback', true)
        ->assertJsonPath('fallback_reason', 'missing_api_key')
        ->assertJsonPath('lots.0.name', 'Central Garage')
        ->assertJsonPath('lots.0.available_spaces', 1)
        ->assertJsonPath('lots.0.total_spaces', 3);
});

test('chat preserves database availability when OpenAI credits are exhausted', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake([
        '*' => Http::response([
            'error' => [
                'message' => 'You have no credits remaining.',
                'code' => 'insufficient_quota',
            ],
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