<?php

namespace App\Enums;

enum Permission: string
{
    case ManageUsers = 'manage-users';
    case ManageRoles = 'manage-roles';
    case ManageCars = 'manage-cars';
    case ManageBookings = 'manage-bookings';
    case ManagePayments = 'manage-payments';
    
    // Hak akses untuk Penyewa & Pengguna
    case CreateBooking = 'create-booking';
    case ViewOwnBooking = 'view-own-booking';
    case CancelOwnBooking = 'cancel-own-booking';
    case ManageProfile = 'manage-profile'; // 👈 Wajib ada untuk update profil / KTP di Flutter

    // Hak akses untuk Driver / Perental
    case ViewAssignedBooking = 'view-assigned-booking';
    case UpdateBookingStatus = 'update-booking-status';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}