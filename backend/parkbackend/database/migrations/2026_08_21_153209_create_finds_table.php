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
        Schema::create('finds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('parking_sessions')
                ->onDelete('set null');
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained('reservations')
                ->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->string('reason', 255);
            $table->timestamp('issue_date');
            $table->string('status', 20)->default('Pending');
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->onDelete('set null');
            $table->timestamps();

            // MySQL indexes
            $table->index('status');
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finds');
    }
};
