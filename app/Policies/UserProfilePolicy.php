<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\Role;

class UserProfilePolicy
{
    /**
     * Tentukan apakah user boleh mengelola/memperbarui profil.
     */
    public function update(User $user): bool
    {
        // 1. Pastikan user memiliki izin dasar untuk mengelola profil
        if (!$user->hasPermissionTo('manage-profile')) {
            return false;
        }

        // 2. Jika user adalah Driver atau Penyewa, kita hanya mencekal jika SIM-nya SUDAH ADA tapi sudah KADALUARSA.
        // Jangan memblokir jika mereka sedang mengisi data untuk pertama kali.
        if ($user->hasRole([Role::Driver->value, Role::Penyewa->value, Role::Perental->value])) {
            $personalData = $user->personalData;

            // Jika data personal dan SIM sudah ada, pastikan masa berlakunya belum habis
            if ($personalData && $personalData->sim_expired_date) {
                if ($personalData->sim_expired_date->isPast()) {
                    return false; // Tolak jika SIM sudah kadaluarsa
                }
            }
        }

        return true;
    }
}