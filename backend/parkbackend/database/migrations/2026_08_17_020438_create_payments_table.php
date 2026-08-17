<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('PaymentID');
            $table->foreignId('ReservationID')->constrained('reservations', 'ReservationID')->cascadeOnDelete();
            $table->foreignId('SessionID')->nullable()->constrained('parking_sessions', 'SessionID')->nullOnDelete();
            $table->decimal('Amount', 10, 2);
            $table->dateTime('PaymentDate')->useCurrent();
            $table->enum('Method', ['Credit Card', 'Debit Card', 'PayPal', 'Cash', 'Mobile Wallet']);
            $table->enum('Status', ['Pending', 'Completed', 'Failed', 'Refunded'])->default('Pending');
            $table->string('TransactionID', 100)->unique()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};