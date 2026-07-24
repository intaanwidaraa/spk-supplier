<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'admin123@gmail.com'],
            [
                'name' => 'SPV Purchasing',
                // Keep existing password, role, etc. or set default if missing.
                'role' => 'admin',
            ]
        );
    }
}
