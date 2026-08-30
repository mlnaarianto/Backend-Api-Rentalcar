<?php

namespace App\Policies;

use App\Models\RentalApplication;
use App\Models\User;
use App\Enums\Permission;

class RentalApplicationPolicy
{
    /**
     * Apakah user boleh melihat daftar pengajuan (Admin / Verifikator)
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo(Permission::VerifyPerental->value);
    }

    /**
     * Apakah user boleh melihat detail pengajuan tertentu
     */
    public function view(User $user, RentalApplication $rentalApplication): bool
    {
        // Pemilik data sendiri boleh melihat, atau admin yang punya izin verifikasi
        return $user->id === $rentalApplication->user_id
            || $user->hasRole('Super Admin')
            || $user->hasPermissionTo(Permission::VerifyPerental->value);
    }

    /**
     * Apakah user boleh memperbarui/mengirim pengajuan (Milik sendiri)
     */
    public function update(User $user, RentalApplication $rentalApplication): bool
    {
        return $user->id === $rentalApplication->user_id;
    }

    /**
     * Apakah user boleh menyetujui/menolak pengajuan (Khusus Admin/Verifikator)
     */
    public function verify(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo(Permission::VerifyPerental->value);
    }
}
