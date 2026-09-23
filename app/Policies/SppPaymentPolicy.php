<?php

namespace App\Policies;

use App\Models\SppPayment;
use App\Models\User;

class SppPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Siswa mengunggah bukti bayar dari akunnya sendiri.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('siswa');
    }

    /**
     * Verifikasi (approve/reject) bukti bayar.
     */
    public function update(User $user, SppPayment $sppPayment): bool
    {
        return $user->hasRole('admin');
    }
}
