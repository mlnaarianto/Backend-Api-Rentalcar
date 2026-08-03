<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Enums\Permission;
use App\Enums\Role;

class BookingPolicy
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
        // 1. Jika punya izin manage booking (Perental/Admin)
        if ($user->hasPermissionTo(Permission::ManageBookings->value)) {
            return $booking->car && $booking->car->user_id === $user->id;
        }

        // 2. Jika punya izin melihat booking yang ditugaskan (Driver)
        if ($user->hasPermissionTo(Permission::ViewAssignedBooking->value)) {
            return $booking->driver_id === $user->id;
        }

        // 3. Jika punya izin melihat booking sendiri (Penyewa)
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
     * Menentukan apakah user bisa mengubah status booking atau melakukan pembayaran.
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

        // 3. Penyewa (milik sendiri, misal untuk bayar QRIS)
        if ($booking->user_id === $user->id) {
            return true;
        }

        return false;
    }
}