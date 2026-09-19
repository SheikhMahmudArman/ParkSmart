<?php

namespace Database\Seeders;

use App\Support\Sql;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['John Driver', 'john@driver.com', 'driver'], ['Sarah Driver', 'sarah@driver.com', 'driver'], ['Staff User', 'staff@parking.com', 'staff'], ['Admin User', 'admin@parking.com', 'admin']] as [$name, $email, $role]) {
            if (!DB::selectOne('SELECT id FROM users WHERE email=?', [$email])) {
                DB::insert('INSERT INTO users(name,email,password,role,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())', [$name, $email, Hash::make('ParkSmart123!'), $role]);
            }
        }
        foreach ([['Downtown Plaza', 'Dhaka city centre', 10, 50], ['Airport Parking', 'Dhaka airport road', 6, 80]] as [$name, $location, $count, $rate]) {
            if (!DB::selectOne('SELECT id FROM parking_lots WHERE name=?', [$name])) {
                Sql::call('sp_create_lot', [$name, $location, $count, $rate, 'Standard', json_encode(['Security', 'Covered'])]);
            }
        }
        foreach ([['john@driver.com', 'DHAKA-123'], ['sarah@driver.com', 'DHAKA-789']] as [$email, $plate]) {
            $user = DB::selectOne('SELECT id FROM users WHERE email=?', [$email]);
            if (!DB::selectOne('SELECT id FROM vehicles WHERE plate_number=?', [$plate])) {
                DB::insert("INSERT INTO vehicles(user_id,plate_number,make,model,created_at,updated_at) VALUES(?,?,'Toyota','Corolla',NOW(),NOW())", [$user->id, $plate]);
            }
        }
        $this->command->info('Demo accounts use ParkSmart123! Existing accounts and passwords are unchanged.');
    }
}
