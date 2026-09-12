<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cloudnewsmeet.com'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'password' => Hash::make('AdminSecurePass2026!'),
                'role' => 'admin',
                'is_guest' => false,
            ]
        );
    }
}
