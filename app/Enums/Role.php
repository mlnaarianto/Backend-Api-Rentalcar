<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Perental = 'Perental';   // Pemilik / Pengelola Rental Mobil
    case Penyewa = 'Penyewa';     // Customer / Penyewa Mobil
    case Driver = 'Driver';       // Sopir (jika ada jasa driver)

    /**
     * Dapatkan daftar permission yang dimiliki oleh masing-masing role.
     * Dipakai HANYA di seeder untuk mengisi tabel permission Spatie —
     * tidak pernah dipakai untuk percabangan logic di controller/service/policy.
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
                // 👇 Ditambahkan: Perental juga bisa berperan sebagai penyewa
                // (menyewa mobil milik Perental lain), sehingga butuh permission
                // yang sama seperti Penyewa untuk fitur ini.
                Permission::CreateBooking,
                Permission::ViewOwnBooking,
                Permission::CancelOwnBooking,
            ],

            self::Penyewa => [
                Permission::ViewCars,
                Permission::CreateBooking,
                Permission::ViewOwnBooking,
                Permission::CancelOwnBooking,
                // 👇 UpdateBookingStatus DICABUT dari Penyewa.
                // Alasan: endpoint /bookings/{id}/status bisa mengubah field
                // 'status' pesanan (bukan cuma payment_status), dan itu
                // seharusnya cuma hak Perental/Driver. Konfirmasi pembayaran
                // Penyewa sekarang lewat /bookings/{id}/check-payment yang
                // baca status asli dari Midtrans, jadi permission ini sudah
                // tidak dibutuhkan sama sekali oleh Penyewa.
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
