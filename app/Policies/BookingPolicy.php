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
     * Menentukan apakah user bisa melihat daftar seluruh/sebagian booking.
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
        if ($user->hasRole(Role::Perental->value)) {
            return $booking->car && $booking->car->user_id === $user->id;
        }

        if ($user->hasRole(Role::Driver->value)) {
            return $booking->driver_id === $user->id;
        }

        return $booking->user_id === $user->id;
    }

    /**
     * Menentukan apakah user bisa membuat booking (Penyewa).
     */
    public function create(User $user): bool
    {
        // 1. Cek permission dasar
        if (!$user->hasPermissionTo(Permission::CreateBooking->value)) {
            return false;
        }

        // 2. 🛡️ Syarat Wajib: Cek apakah user sudah melengkapi Data Personal (No. HP, KTP, & SIM)
        $personalData = $user->personalData;
        if (!$personalData || empty($personalData->phone) || empty($personalData->ktp_image) || empty($personalData->sim_number) || empty($personalData->sim_image)) {
            return false;
        }

        // 3. 🛡️ Syarat Wajib: Cegah buat booking baru jika masih ada pesanan berstatus 'pending'
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

        // 1. Jika user adalah Penyewa, hanya boleh mengubah/membayar booking miliknya sendiri (misal: bayar QRIS)
        if ($user->hasRole(Role::Penyewa->value)) {
            return $booking->user_id === $user->id;
        }

        // 2. Jika user adalah Perental, pastikan booking terkait mobil miliknya 
        if ($user->hasRole(Role::Perental->value)) {
            return $booking->car && $booking->car->user_id === $user->id;
        }

        // 3. Jika user adalah Driver, pastikan dia memang ditugaskan ke booking tersebut
        if ($user->hasRole(Role::Driver->value)) {
            return $booking->driver_id === $user->id;
        }

        return false;
    }
}