<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\Permission;

class UserProfilePolicy
{
    /**
     * Tentukan apakah user boleh mengelola/memperbarui profil.
     */
    public function update(User $user): bool
    {
        // Cukup pastikan user memiliki izin dasar (Permission ManageProfile)
        return $user->hasPermissionTo(Permission::ManageProfile->value);
    }
}