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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained('reservations')
                ->onDelete('set null');
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('parking_sessions')
                ->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->timestamp('payment_date');
            $table->string('method', 50);
            $table->string('status', 20)->default('Completed');
            $table->string('transaction_id', 100)->nullable();
            $table->timestamps();

            // MySQL indexes
            $table->index('payment_date');
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
