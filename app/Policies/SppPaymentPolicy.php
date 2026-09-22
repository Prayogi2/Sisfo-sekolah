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
     * Baik wali maupun siswa yang login sendiri boleh mengunggah bukti
     * bayar (route & halamannya sama-sama terbuka untuk kedua role ini).
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['wali', 'siswa']);
    }

    /**
     * Verifikasi (approve/reject) bukti bayar.
     */
    public function update(User $user, SppPayment $sppPayment): bool
    {
        return $user->hasRole('admin');
    }
}
