<?php

namespace App\Policies;

use App\Models\Car;
use App\Models\User;
use App\Enums\Permission;
use App\Enums\Role;

class CarPolicy
{
    /**
     * Super Admin otomatis diizinkan untuk semua tindakan.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(Role::SuperAdmin->value)) {
            return true;
        }

        return null; 
    }

    /**
     * Menentukan apakah user bisa melihat daftar mobil.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ViewCars->value) 
            || $user->hasPermissionTo(Permission::ManageCars->value);
    }

    /**
     * Menentukan apakah user bisa melihat detail mobil tertentu.
     * Jika Perental, pastikan mereka hanya melihat mobil miliknya (atau bisa disesuaikan jika ingin publik).
     */
    public function view(User $user, Car $car): bool
    {
        return $user->hasPermissionTo(Permission::ViewCars->value) 
            || $user->hasPermissionTo(Permission::ManageCars->value);
    }

    /**
     * Menentukan apakah user bisa membuat mobil baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageCars->value);
    }

    /**
     * Menentukan apakah user bisa memperbarui data mobil.
     * Harus punya permission manage-cars DAN merupakan pemilik mobil tersebut.
     */
    public function update(User $user, Car $car): bool
    {
        return $user->hasPermissionTo(Permission::ManageCars->value) 
            && $car->user_id === $user->id;
    }

    /**
     * Menentukan apakah user bisa menghapus mobil.
     * Harus punya permission manage-cars DAN merupakan pemilik mobil tersebut.
     */
    public function delete(User $user, Car $car): bool
    {
        return $user->hasPermissionTo(Permission::ManageCars->value) 
            && $car->user_id === $user->id;
    }

    /**
     * Menentukan apakah user bisa merestore mobil yang dihapus.
     */
    public function restore(User $user, Car $car): bool
    {
        return $user->hasPermissionTo(Permission::ManageCars->value) 
            && $car->user_id === $user->id;
    }

    /**
     * Menentukan apakah user bisa menghapus permanen mobil.
     */
    public function forceDelete(User $user, Car $car): bool
    {
        return $user->hasRole(Role::SuperAdmin->value);
    }
}