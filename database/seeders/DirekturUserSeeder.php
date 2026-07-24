<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DirekturUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'direktur@spksupplier.test'],
            [
                'name' => 'Direktur',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'direktur',
            ]
        );
    }
}
