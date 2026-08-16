<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@parking.com',
            'password' => '123456',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Staff User',
            'email' => 'staff@parking.com',
            'password' => '123456',
            'role' => 'staff',
        ]);

        User::create([
            'name' => 'Normal User',
            'email' => 'user@parking.com',
            'password' => '123456',
            'role' => 'user',
        ]);
    }
}