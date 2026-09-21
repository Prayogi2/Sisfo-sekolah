<?php

namespace App\Policies;

use App\Models\User;

class SppBillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Dipakai untuk generate tagihan bulanan massal.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
