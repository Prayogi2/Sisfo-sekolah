<?php

namespace App\Policies;

use App\Models\User;

class FeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
