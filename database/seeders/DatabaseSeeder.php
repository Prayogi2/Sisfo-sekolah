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
        // 1. Akun Admin
        User::create([
            'name' => 'Administrator Sekolah',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Akun Guru
        User::create([
            'name' => 'Budi Santoso, S.Pd.',
            'email' => 'guru@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        // 3. Akun Siswa
        User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'siswa@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        // 4. Akun Wali Murid
        User::create([
            'name' => 'Wali dari Ahmad Fauzi',
            'email' => 'wali@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'wali',
        ]);
    }
}
