<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman form login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Memproses autentikasi login multi-role.
     */
    public function login(Request $request)
    {
        // 1. Validasi Input dari Form
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
            'role'     => 'required|in:admin,guru,siswa,wali',
        ], [
            'login.required'    => 'Field login wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'role.required'     => 'Peran/Role harus dipilih.',
            'role.in'           => 'Peran/Role tidak valid.',
        ]);

        $input = $request->input('login');
        $password = $request->input('password');
        $role = $request->input('role');
        $remember = $request->has('remember');

        // 2. Deteksi field login (email / username / nip / nisn)
        $field = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Susun kredensial pencocokan
        $credentials = [
            $field    => $input,
            'password' => $password,
            'role'     => $role,
        ];

        // 3. Coba Autentikasi
        if (Auth::attempt($credentials, $remember)) {
            // Mencegah Session Fixation Attack
            $request->session()->regenerate();

            // Redirect pengguna ke dashboard sesuai role
            return match ($role) {
                'admin' => redirect()->route('admin.dashboard'),
                'guru'  => redirect()->route('guru.dashboard'),
                'siswa' => redirect()->route('siswa.dashboard'),
                'wali'  => redirect()->route('wali.dashboard'),
                default => redirect()->to('/'),
            };
        }

        // 4. Pengondisian jika login gagal
        return back()
            ->with('error', 'Kredensial atau role yang Anda pilih tidak cocok.')
            ->withInput($request->only('login', 'role'));
    }

    /**
     * Memproses logout pengguna.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidate session dan regenerasi CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}