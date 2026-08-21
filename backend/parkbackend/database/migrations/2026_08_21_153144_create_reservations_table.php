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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->onDelete('cascade');
            $table->foreignId('space_id')
                ->constrained('parking_spaces')
                ->onDelete('cascade');
            $table->date('reservation_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('Pending');
            $table->string('payment_status', 20)->default('Pending');
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->timestamp('reservation_time')->nullable();
            $table->timestamps();

            // MySQL indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('reservation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
