<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserPasswordResetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->whereIn('role', ['guru', 'siswa'])
            ->orderBy('role')->orderBy('name')->get();

        return view('admin.akun', compact('users'));
    }

    public function resetPassword(User $user, UserPasswordResetter $resetter): RedirectResponse
    {
        abort_unless(in_array($user->role, ['guru', 'siswa'], true), 404);

        return back()->with('success', "Password baru untuk {$user->name}: ".$resetter->reset($user));
    }
}
