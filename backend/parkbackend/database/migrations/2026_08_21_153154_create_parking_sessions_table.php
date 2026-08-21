<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('parking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained('reservations')
                ->onDelete('set null');
            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->onDelete('cascade');
            $table->foreignId('space_id')
                ->constrained('parking_spaces')
                ->onDelete('cascade');
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('hourly_rate', 10, 2)->default(5.00);
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->timestamps();

            // MySQL indexes
            $table->index('entry_time');
            $table->index('exit_time');
            $table->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_sessions');
    }
};
