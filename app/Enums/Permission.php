<?php

namespace App\Enums;

enum Permission: string
{
    // Manajemen Sistem & User
    case ManageUsers = 'manage-users';
    case ManageRoles = 'manage-roles';

    // Manajemen Verifikasi Perental
    case VerifyPerental = 'verify-perental';

    // Manajemen Mobil (Mobil & Katalog)
    case ManageCars = 'manage-cars'; // Untuk tambah/edit/hapus (Perental/Admin)
    case ViewCars = 'view-cars';     // Untuk melihat daftar/detail mobil (Bisa untuk Penyewa/Driver)

    // Manajemen Booking & Pembayaran
    case ManageBookings = 'manage-bookings';           // Perental: kelola booking untuk mobil miliknya
    case ManageAllBookings = 'manage-all-bookings';     // 👈 BARU: admin-level, lihat & kelola SEMUA booking lintas Perental
    case ManagePayments = 'manage-payments';

    // Hak akses untuk Penyewa & Pengguna
    case CreateBooking = 'create-booking';
    case ViewOwnBooking = 'view-own-booking';
    case CancelOwnBooking = 'cancel-own-booking';
    case ManageProfile = 'manage-profile'; // Wajib ada untuk update profil / KTP di Flutter

    // Hak akses untuk Driver / Perental
    case ViewAssignedBooking = 'view-assigned-booking';
    case UpdateBookingStatus = 'update-booking-status'; // Hanya Perental & Driver (bukan Penyewa)

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}