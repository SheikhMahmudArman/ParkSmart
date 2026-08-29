<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ParkingLot;
use App\Models\ParkingSpace;
use App\Models\Vehicle;
use App\Models\Reservation;
use App\Models\ParkingSession;
use App\Models\Payment;
use App\Models\Find;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Users
        $user1 = User::create([
            'name' => 'John Driver',
            'email' => 'john@driver.com',
            'password' => Hash::make('123456'),
            'role' => 'driver'
        ]);

        $user2 = User::create([
            'name' => 'Sarah Driver',
            'email' => 'sarah@driver.com',
            'password' => Hash::make('123456'),
            'role' => 'driver'
        ]);

        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@parking.com',
            'password' => Hash::make('123456'),
            'role' => 'staff'
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@parking.com',
            'password' => Hash::make('123456'),
            'role' => 'admin'
        ]);

        // 2. Create Parking Lots (with all columns)
        $lot1 = ParkingLot::create([
            'name' => 'Downtown Plaza',
            'location' => '123 Main St, NYC',
            'total_spaces' => 50,
            'available_spaces' => 50,
            'hourly_rate' => 5.00,
            'type' => 'Standard',
            'features' => 'EV Charging, Covered'
        ]);

        $lot2 = ParkingLot::create([
            'name' => 'Airport Terminal',
            'location' => '789 Airport Rd, SF',
            'total_spaces' => 30,
            'available_spaces' => 30,
            'hourly_rate' => 8.00,
            'type' => 'Premium',
            'features' => 'Valet, 24/7 Security'
        ]);

        // 3. Create Parking Spaces (using parking_lot_id as foreign key)
        for ($i = 1; $i <= 10; $i++) {
            ParkingSpace::create([
                'parking_lot_id' => $lot1->id,
                'space_number' => 'A' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'status' => $i <= 7 ? 'Available' : 'Occupied',
                'type' => $i % 2 == 0 ? 'EV' : 'Standard'
            ]);
        }

        for ($i = 1; $i <= 6; $i++) {
            ParkingSpace::create([
                'parking_lot_id' => $lot2->id,
                'space_number' => 'C' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'status' => $i <= 3 ? 'Available' : 'Occupied',
                'type' => 'Premium'
            ]);
        }

        // 4. Create Vehicles
        $vehicle1 = Vehicle::create([
            'user_id' => $user1->id,
            'plate_number' => 'ABC-123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'color' => 'Black'
        ]);

        $vehicle2 = Vehicle::create([
            'user_id' => $user2->id,
            'plate_number' => 'XYZ-789',
            'make' => 'Honda',
            'model' => 'Civic',
            'color' => 'White'
        ]);

        // 5. Create Reservations
       $res1 = Reservation::create([
         'user_id' => $user1->id,
         'vehicle_id' => $vehicle1->id,
        'space_id' => 1,
         'reservation_date' => now()->toDateString(),
         'start_time' => now()->subHours(2),
        'end_time' => now()->addHours(2),
        'status' => 'Active',
        'total_amount' => 10.00,
        'payment_status' => 'Paid'
    ]);

       $res2 = Reservation::create([
    'user_id' => $user2->id,
    'vehicle_id' => $vehicle2->id,
    'space_id' => 3,
    'reservation_date' => now()->subDays(1)->toDateString(),
    'start_time' => now()->subDays(1)->subHours(3),
    'end_time' => now()->subDays(1)->subHours(1),
    'status' => 'Completed',
    'total_amount' => 16.00,
    'payment_status' => 'Paid'
]);

        $res3 = Reservation::create([
            'user_id' => $user1->id,
            'vehicle_id' => $vehicle1->id,
            'space_id' => 5,
            'reservation_date' => now()->toDateString(),
            'start_time' => now()->subDays(2)->subHours(4),
            'end_time' => now()->subDays(2)->subHours(2),
            'status' => 'Completed',
            'total_amount' => 8.00,
            'payment_status' => 'Paid'
        ]);

        // 6. Create Parking Sessions
        $session1 = ParkingSession::create([
            'reservation_id' => $res1->id,
            'vehicle_id' => $vehicle1->id,
            'space_id' => 1,
            'entry_time' => now()->subHours(2),
            'exit_time' => null,
            'duration_minutes' => null,
            'hourly_rate' => 5.00,
            'total_cost' => null
        ]);

        $session2 = ParkingSession::create([
            'reservation_id' => $res2->id,
            'vehicle_id' => $vehicle2->id,
            'space_id' => 3,
            'entry_time' => now()->subDays(1)->subHours(3),
            'exit_time' => now()->subDays(1)->subHours(1),
            'duration_minutes' => 120,
            'hourly_rate' => 8.00,
            'total_cost' => 16.00
        ]);

        $session3 = ParkingSession::create([
            'reservation_id' => $res3->id,
            'vehicle_id' => $vehicle1->id,
            'space_id' => 5,
            'entry_time' => now()->subDays(2)->subHours(4),
            'exit_time' => now()->subDays(2)->subHours(2),
            'duration_minutes' => 120,
            'hourly_rate' => 4.00,
            'total_cost' => 8.00
        ]);

        // 7. Create Payments (Completed)
        Payment::create([
            'reservation_id' => $res1->id,
            'session_id' => $session1->id,
            'amount' => 10.00,
            'payment_date' => now(),
            'method' => 'Credit Card',
            'status' => 'Completed',
            'transaction_id' => 'TXN-001'
        ]);

        Payment::create([
            'reservation_id' => $res2->id,
            'session_id' => $session2->id,
            'amount' => 16.00,
            'payment_date' => now()->subDays(1),
            'method' => 'PayPal',
            'status' => 'Completed',
            'transaction_id' => 'TXN-002'
        ]);

        Payment::create([
            'reservation_id' => $res3->id,
            'session_id' => $session3->id,
            'amount' => 8.00,
            'payment_date' => now()->subDays(2),
            'method' => 'Cash',
            'status' => 'Completed',
            'transaction_id' => 'TXN-003'
        ]);

        // 8. Create Finds (Penalties)
        Find::create([
            'session_id' => $session1->id,
            'reservation_id' => $res1->id,
            'amount' => 25.00,
            'reason' => 'Overstayed by 1 hour 15 minutes',
            'issue_date' => now()->subHours(1),
            'status' => 'Pending',
            'payment_id' => null
        ]);

        Find::create([
            'session_id' => $session2->id,
            'reservation_id' => $res2->id,
            'amount' => 15.00,
            'reason' => 'Parked in EV spot without charging',
            'issue_date' => now()->subDays(1),
            'status' => 'Paid',
            'payment_id' => 2
        ]);

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('👤 Users: john@driver.com, sarah@driver.com, staff@parking.com, admin@parking.com');
        $this->command->info('🔑 Password for all: 123456');
    }
}