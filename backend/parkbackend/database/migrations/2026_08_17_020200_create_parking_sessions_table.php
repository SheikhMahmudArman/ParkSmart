<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_sessions', function (Blueprint $table) {
            $table->id('SessionID');
            $table->foreignId('ReservationID')->constrained('reservations', 'id')->cascadeOnDelete();
            $table->foreignId('VehicleID')->constrained('vehicles', 'id')->cascadeOnDelete();
            $table->foreignId('SpaceID')->constrained('parking_spaces', 'id')->cascadeOnDelete();
            $table->dateTime('EntryTime');
            $table->dateTime('ExitTime')->nullable();
            $table->integer('DurationMinutes')->nullable();
            $table->decimal('HourlyRate', 10, 2);
            $table->decimal('TotalCost', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_sessions');
    }
};