<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Mahasiswa 1',
            'email' => 'mahasiswa1@example.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        User::create([
            'name' => 'Mahasiswa 2',
            'email' => 'mahasiswa2@example.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);
    }
}
