<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Find;
use App\Models\ParkingSession;
use App\Models\Reservation;

class FindSeeder extends Seeder
{
    public function run(): void
    {
        $session = ParkingSession::first();
        $reservation = Reservation::first();

        if (!$session || !$reservation) {
            $this->command->info('⚠️ Missing session/reservation data. Run main seeder first.');
            return;
        }

        Find::create([
            'SessionID' => $session->SessionID,
            'ReservationID' => $reservation->ReservationID,
            'Amount' => 25.00,
            'Reason' => 'Overstayed by 1 hour 15 minutes beyond reservation time',
            'IssueDate' => now()->subHours(3),
            'Status' => 'Pending',
            'PaymentID' => null
        ]);

        Find::create([
            'SessionID' => $session->SessionID,
            'ReservationID' => $reservation->ReservationID,
            'Amount' => 15.00,
            'Reason' => 'Vehicle parked in EV spot without charging',
            'IssueDate' => now()->subDays(2),
            'Status' => 'Paid',
            'PaymentID' => 1 // Assuming first payment is for this find
        ]);

        Find::create([
            'SessionID' => $session->SessionID,
            'ReservationID' => $reservation->ReservationID,
            'Amount' => 50.00,
            'Reason' => 'Blocked emergency exit',
            'IssueDate' => now()->subDays(5),
            'Status' => 'Pending',
            'PaymentID' => null
        ]);

        $this->command->info('✅ Find seeder completed.');
    }
}