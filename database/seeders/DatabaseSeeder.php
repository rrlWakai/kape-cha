<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Development/demo seed accounts for ITST 302.
        // Passwords are hashed via Laravel's bcrypt hashing — never stored plain.
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['username' => 'staff'],
            [
                'password' => Hash::make('staff123'),
                'role' => User::ROLE_STAFF,
            ]
        );

        $this->call(ProductSeeder::class);
    }
}
