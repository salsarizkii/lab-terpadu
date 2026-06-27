<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $this->call(PermissionsSeeder::class);

        // Seed only superadmin role (admin/analyst/user must be created via UI)
        Role::firstOrCreate(
            ['name' => 'superadmin'],
            [
                'description' => 'Super Administrator dengan akses penuh ke seluruh sistem',
                'is_system' => true,
            ]
        );

        // Seed only 1 superadmin account
        User::firstOrCreate(
            ['email' => 'superadmin@lab.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => 'superadmin',
                'is_verified' => true,
            ]
        );
    }
}
