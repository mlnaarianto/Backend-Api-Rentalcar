<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Enums\Permission;

class BookingPolicy
{
    /**
     * Pemegang permission 'manage-all-bookings' bypass semua ability di
     * policy ini (setara "admin penuh" untuk modul booking). Permission-based,
     * bukan hasRole() — siapapun yang diberi permission ini lewat seeder
     * otomatis dapat akses penuh, apapun nama role-nya.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasPermissionTo(Permission::ManageAllBookings->value)) {
            return true;
        }

        return null;
    }

    /**
     * Menentukan apakah user bisa melihat daftar booking.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageBookings->value)
            || $user->hasPermissionTo(Permission::ViewOwnBooking->value)
            || $user->hasPermissionTo(Permission::ViewAssignedBooking->value);
    }

    /**
     * Menentukan apakah user bisa melihat detail booking tertentu.
     */
    public function view(User $user, Booking $booking): bool
    {
        // 1. Perental (pemilik mobil terkait)
        if ($user->hasPermissionTo(Permission::ManageBookings->value)) {
            return $booking->car && $booking->car->user_id === $user->id;
        }

        // 2. Driver yang ditugaskan
        if ($user->hasPermissionTo(Permission::ViewAssignedBooking->value)) {
            return $booking->driver_id === $user->id;
        }

        // 3. Penyewa (miliknya sendiri)
        return $user->hasPermissionTo(Permission::ViewOwnBooking->value)
            && $booking->user_id === $user->id;
    }

    /**
     * Menentukan apakah user bisa membuat booking (Penyewa).
     */
    public function create(User $user): bool
    {
        if (!$user->hasPermissionTo(Permission::CreateBooking->value)) {
            return false;
        }

        $personalData = $user->personalData;
        if (!$personalData || empty($personalData->phone) || empty($personalData->ktp_image) || empty($personalData->sim_number) || empty($personalData->sim_image)) {
            return false;
        }

        $hasPendingBooking = Booking::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingBooking) {
            return false;
        }

        return true;
    }

    /**
     * Menentukan apakah user bisa membatalkan booking miliknya sendiri.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->hasPermissionTo(Permission::CancelOwnBooking->value)
            && $booking->user_id === $user->id;
    }

    /**
     * Menentukan apakah user bisa mengubah status booking (field 'status'
     * dan/atau 'payment_status' manual).
     *
     * 👇 Penyewa SENGAJA tidak diberi akses di sini. Field 'status' pesanan
     * (pending/confirmed/active/completed/cancelled) adalah hak Perental &
     * Driver. Konfirmasi pembayaran Penyewa lewat jalur terpisah yang aman:
     * BookingController::checkPayment(), yang membaca status asli dari
     * Midtrans — bukan endpoint generic ini.
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        if (!$user->hasPermissionTo(Permission::UpdateBookingStatus->value)) {
            return false;
        }

        // 1. Perental (pemilik mobil)
        if ($booking->car && $booking->car->user_id === $user->id) {
            return true;
        }

        // 2. Driver yang ditugaskan
        if ($booking->driver_id === $user->id) {
            return true;
        }

        return false;
    }

    // Catatan: BookingController::checkPayment() memakai
    // $this->authorize('view', $booking) — jadi otomatis mengikuti aturan
    // view() di atas (Perental/Driver/Penyewa pemilik booking berhak cek
    // status pembayaran booking tersebut). Tidak perlu method policy terpisah.
}