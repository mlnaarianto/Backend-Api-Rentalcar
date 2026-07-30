<?php

namespace App\Enums;

use Althinect\FilamentSpatieRolesPermissions\Contracts\RoleEnum;

enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Perental = 'Perental';   // Pemilik / Pengelola Rental Mobil
    case Penyewa = 'Penyewa';     // Customer / Penyewa Mobil
    case Driver = 'Driver';       // Sopir (jika ada jasa driver)

    public function permissions(): array
    {
        return match ($this) {
            Role::SuperAdmin => Permission::cases(), // Super Admin memegang semua hak akses
            
            Role::Perental => [
                Permission::ManageCars,
                Permission::ManageBookings,
                Permission::ManagePayments,
                Permission::UpdateBookingStatus,
                Permission::ManageProfile, // ✅ Perental bisa update profil
            ],
            
            Role::Penyewa => [
                Permission::CreateBooking,
                Permission::ViewOwnBooking,
                Permission::CancelOwnBooking,
                Permission::ManageProfile, // ✅ Penyewa bisa update profil & KTP
            ],
            
            Role::Driver => [
                Permission::ViewAssignedBooking,
                Permission::UpdateBookingStatus,
                Permission::ManageProfile, // ✅ Driver bisa update profil
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}