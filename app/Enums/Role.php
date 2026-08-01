<?php

namespace App\Enums;

// Removed undefined RoleEnum interface import

enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Perental = 'Perental';   // Pemilik / Pengelola Rental Mobil
    case Penyewa = 'Penyewa';    // Customer / Penyewa Mobil
    case Driver = 'Driver';      // Sopir (jika ada jasa driver)

    public function permissions(): array
    {
        return match ($this) {
            Role::SuperAdmin => Permission::cases(), // Super Admin memegang semua hak akses
            
            Role::Perental => [
                Permission::ManageCars,
                Permission::ViewCars,        // 👈 Ditambahkan
                Permission::ManageBookings,
                Permission::ManagePayments,
                Permission::UpdateBookingStatus,
                Permission::ManageProfile, 
            ],
            
            Role::Penyewa => [
                Permission::ViewCars,        // Untuk akses list/detail mobil
                Permission::CreateBooking,
                Permission::ViewOwnBooking,
                Permission::CancelOwnBooking,
                Permission::UpdateBookingStatus, // 👈 Ditambahkan agar Penyewa bisa mengubah status (membayar QRIS)
                Permission::ManageProfile, 
            ],
            
            Role::Driver => [
                Permission::ViewCars,        // 👈 Ditambahkan (opsional jika driver butuh lihat list mobil)
                Permission::ViewAssignedBooking,
                Permission::UpdateBookingStatus,
                Permission::ManageProfile, 
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}