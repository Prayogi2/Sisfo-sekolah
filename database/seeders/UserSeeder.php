<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'password' => Hash::make('password123'), // Kata sandi untuk login
            'role' => 'admin',
        ]);

        // 2. Akun Guru
        User::create([
            'name' => 'Guru Pengajar',
            'email' => 'guru@gmail.com',
            'username' => 'guru',
            'password' => Hash::make('password123'),
            'role' => 'guru',
        ]);

        // 3. Akun Siswa
        User::create([
            'name' => 'Budi',
            'email' => 'siswa@gmail.com',
            'username' => 'siswa',
            'password' => Hash::make('password123'),
            'role' => 'siswa',
        ]);
    }
}
