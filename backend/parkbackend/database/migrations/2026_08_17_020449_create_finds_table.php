<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finds', function (Blueprint $table) {
            $table->id('FindID');
            $table->foreignId('SessionID')->constrained('parking_sessions', 'SessionID')->cascadeOnDelete();
            $table->foreignId('ReservationID')->constrained('reservations', 'ReservationID')->cascadeOnDelete();
            $table->decimal('Amount', 10, 2);
            $table->string('Reason', 200);
            $table->dateTime('IssueDate')->useCurrent();
            $table->enum('Status', ['Pending', 'Paid', 'Waived'])->default('Pending');
            $table->foreignId('PaymentID')->nullable()->constrained('payments', 'PaymentID')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finds');
    }
};