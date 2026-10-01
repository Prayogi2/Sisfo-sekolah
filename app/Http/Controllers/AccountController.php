<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserPasswordResetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Session key penanda admin sedang masuk ke akun guru/siswa.
     */
    public const IMPERSONATOR_KEY = 'impersonator_id';

    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->whereIn('role', ['guru', 'siswa'])
            ->orderBy('role')->orderBy('name')->get();

        return view('admin.akun', compact('users'));
    }

    public function resetPassword(User $user, UserPasswordResetter $resetter): RedirectResponse
    {
        abort_unless(in_array($user->role, ['guru', 'siswa'], true), 404);

        return back()->with('success', "Password baru untuk {$user->name}: ".$resetter->reset($user));
    }

    /**
     * Admin masuk ke akun guru/siswa tanpa perlu tahu password-nya, untuk
     * membantu atau mengecek tampilan akun tersebut.
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['guru', 'siswa'], true), 404);

        if ($user->hasRole('admin')) {
            return back()->with('error', "Akun {$user->name} juga memiliki akses admin, jadi tidak bisa dimasuki dari sini.");
        }

        $admin = $request->user();
        Log::info('Admin masuk ke akun pengguna lain.', ['admin_id' => $admin->id, 'user_id' => $user->id]);

        Auth::login($user);
        $request->session()->put(self::IMPERSONATOR_KEY, $admin->id);

        return redirect()->route($user->role === 'guru' ? 'guru.dashboard' : 'siswa.dashboard')
            ->with('success', "Anda sekarang masuk sebagai {$user->name}.");
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $admin = User::find($request->session()->pull(self::IMPERSONATOR_KEY));

        abort_unless($admin?->hasRole('admin'), 403);

        Log::info('Admin kembali ke akunnya sendiri.', ['admin_id' => $admin->id, 'user_id' => $request->user()->id]);
        Auth::login($admin);

        return redirect()->route('admin.akun')->with('success', 'Anda kembali ke akun admin.');
    }
}
