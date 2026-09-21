<?php

namespace Database\Seeders;

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
        // 1. Akun Admin
        User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@gmail.com',
            'username' => 'admin',
            'password' => Hash::make('password123'), // Kata sandi untuk login
            'role'     => 'admin',
        ]);

        // 2. Akun Guru
        User::create([
            'name'     => 'Guru Pengajar',
            'email'    => 'guru@gmail.com',
            'username' => 'guru',
            'password' => Hash::make('password123'),
            'role'     => 'guru',
        ]);

        // 3. Akun Siswa
        User::create([
            'name'     => 'Siswa Teladan',
            'email'    => 'siswa@gmail.com',
            'username' => 'siswa',
            'password' => Hash::make('password123'),
            'role'     => 'siswa',
        ]);

        // 4. Akun Wali Murid
        User::create([
            'name'     => 'Wali Murid',
            'email'    => 'wali@gmail.com',
            'username' => 'wali',
            'password' => Hash::make('password123'),
            'role'     => 'wali',
        ]);
    }
}