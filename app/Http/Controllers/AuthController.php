<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'identifier.required' => 'Email atau nama wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $identifier = $request->string('identifier')->toString();
        $user = User::query()->where(function ($query) use ($identifier) {
            $query->where(function ($query) use ($identifier) {
                $query->whereIn('role', ['admin', 'guru'])->where('email', $identifier);
            })->orWhere(function ($query) use ($identifier) {
                $query->whereIn('role', ['wali', 'siswa'])->where('name', $identifier);
            });
        })->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return back()
                ->with('error', 'Email/nama atau password salah.')
                ->withInput($request->only('identifier'));
        }

        Auth::login($user, $request->boolean('remember'));
        $role = $user->role;

        if (! in_array($role, ['admin', 'guru', 'wali', 'siswa'], true)) {
            Auth::logout();

            return back()
                ->with('error', 'Akun ini tidak memiliki akses login.')
                ->withInput($request->only('identifier'));
        }

        $request->session()->regenerate();

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'guru' => redirect()->route('guru.dashboard'),
            'wali' => redirect()->route('wali.dashboard'),
            'siswa' => redirect()->route('siswa.dashboard'),
        };
    }

    public function editPassword(): View
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
