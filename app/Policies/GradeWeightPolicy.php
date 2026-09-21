<?php

namespace App\Policies;

use App\Models\User;

class GradeWeightPolicy
{
    /**
     * Bobot nilai hanya boleh diubah admin supaya seragam antar guru.
     */
    public function update(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
