<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ParkingSession;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $reservation = Reservation::first();
        $session = ParkingSession::first();

        if (!$reservation) {
            $this->command->info('⚠️ Missing reservation data. Run main seeder first.');
            return;
        }

        Payment::create([
            'ReservationID' => $reservation->ReservationID,
            'SessionID' => $session?->SessionID,
            'Amount' => 10.00,
            'PaymentDate' => now(),
            'Method' => 'Credit Card',
            'Status' => 'Completed',
            'TransactionID' => 'TXN-' . uniqid()
        ]);

        Payment::create([
            'ReservationID' => $reservation->ReservationID,
            'SessionID' => $session?->SessionID,
            'Amount' => 16.00,
            'PaymentDate' => now()->subDays(1),
            'Method' => 'PayPal',
            'Status' => 'Completed',
            'TransactionID' => 'TXN-' . uniqid()
        ]);

        Payment::create([
            'ReservationID' => $reservation->ReservationID,
            'SessionID' => null,
            'Amount' => 25.00,
            'PaymentDate' => now()->subHours(3),
            'Method' => 'Cash',
            'Status' => 'Pending',
            'TransactionID' => null
        ]);

        $this->command->info('✅ Payment seeder completed.');
    }
}