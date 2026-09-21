<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Reset password akun (guru/wali) atas permintaan admin. Password lama
 * tidak pernah ditampilkan (tetap di-hash) — admin hanya diberi password
 * baru satu kali untuk disampaikan manual ke pemilik akun.
 */
class UserPasswordResetter
{
    public function reset(User $user): string
    {
        $newPassword = Str::password(12);

        $user->update(['password' => $newPassword]);

        return $newPassword;
    }
}
