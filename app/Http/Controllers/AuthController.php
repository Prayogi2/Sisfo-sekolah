<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login secara langsung
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses otentikasi login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Direct ke halaman sesuai role
            return $this->redirectUserByRole(Auth::user()->role);
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Proses logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Pengalihan halaman sesuai hak akses
     */
    private function redirectUserByRole($role)
    {
        switch ($role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'guru':
                return redirect()->route('guru.dashboard');
            case 'siswa':
                return redirect()->route('siswa.jadwal'); // Siswa langsung ke jadwal
            case 'wali':
                return redirect()->route('wali.dashboard');
            default:
                Auth::logout();
                return redirect()->route('login');
        }
    }
}