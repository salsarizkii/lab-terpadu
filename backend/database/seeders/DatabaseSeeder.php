<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@lab.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'analyst@lab.com'],
            [
                'name' => 'Analyst',
                'password' => bcrypt('password'),
                'role' => 'analyst',
            ]
        );

        User::firstOrCreate(
            ['email' => 'user@lab.com'],
            [
                'name' => 'User',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );
    }
}
