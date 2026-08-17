<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParkingSession;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\ParkingSpace;

class ParkingSessionSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing records from teammates' tables
        $reservation = Reservation::first();
        $vehicle = Vehicle::first();
        $space = ParkingSpace::first();

        if (!$reservation || !$vehicle || !$space) {
            $this->command->info('⚠️ Missing related data. Run main seeder first.');
            return;
        }

        ParkingSession::create([
            'ReservationID' => $reservation->ReservationID,
            'VehicleID' => $vehicle->VehicleID,
            'SpaceID' => $space->SpaceID,
            'EntryTime' => now()->subHours(2),
            'ExitTime' => null,
            'DurationMinutes' => null,
            'HourlyRate' => 5.00,
            'TotalCost' => null
        ]);

        ParkingSession::create([
            'ReservationID' => $reservation->ReservationID,
            'VehicleID' => $vehicle->VehicleID,
            'SpaceID' => $space->SpaceID,
            'EntryTime' => now()->subHours(5),
            'ExitTime' => now()->subHours(3),
            'DurationMinutes' => 120,
            'HourlyRate' => 5.00,
            'TotalCost' => 10.00
        ]);

        ParkingSession::create([
            'ReservationID' => $reservation->ReservationID,
            'VehicleID' => $vehicle->VehicleID,
            'SpaceID' => $space->SpaceID,
            'EntryTime' => now()->subDays(1)->subHours(3),
            'ExitTime' => now()->subDays(1)->subHours(1),
            'DurationMinutes' => 120,
            'HourlyRate' => 8.00,
            'TotalCost' => 16.00
        ]);

        $this->command->info('✅ ParkingSession seeder completed.');
    }
}