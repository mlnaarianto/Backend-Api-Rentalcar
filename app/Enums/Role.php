<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Perental = 'Perental';   // Pemilik / Pengelola Rental Mobil
    case Penyewa = 'Penyewa';    // Customer / Penyewa Mobil
    case Driver = 'Driver';      // Sopir (jika ada jasa driver)

    /**
     * Dapatkan daftar permission yang dimiliki oleh masing-masing role.
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(), // Super Admin memegang semua hak akses
            
            self::Perental => [
                Permission::ManageCars,
                Permission::ViewCars,        
                Permission::ManageBookings,
                Permission::ManagePayments,
                Permission::UpdateBookingStatus,
                Permission::ManageProfile, 
            ],
            
            self::Penyewa => [
                Permission::ViewCars,        
                Permission::CreateBooking,
                Permission::ViewOwnBooking,
                Permission::CancelOwnBooking,
                Permission::UpdateBookingStatus, 
                Permission::ManageProfile, 
            ],
            
            self::Driver => [
                Permission::ViewCars,        
                Permission::ViewAssignedBooking,
                Permission::UpdateBookingStatus,
                Permission::ManageProfile, 
            ],
        };
    }

    /**
     * Ambil semua nilai string dari enum Role.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}