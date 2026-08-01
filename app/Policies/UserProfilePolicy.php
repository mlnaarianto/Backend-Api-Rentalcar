<?php

namespace App\Policies;

use App\Models\User;

class UserProfilePolicy
{
    /**
     * Tentukan apakah user boleh mengelola/memperbarui profil.
     */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('manage-profile');
    }
}