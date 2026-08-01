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
        // Jika Perental, pastikan booking terkait mobil miliknya
        if ($user->hasRole(Role::Perental->value)) {
            return $booking->car && $booking->car->user_id === $user->id;
        }

        // Jika Driver, pastikan ditugaskan ke booking tersebut
        if ($user->hasRole(Role::Driver->value)) {
            return $booking->driver_id === $user->id;
        }

        // Jika Penyewa, pastikan itu miliknya sendiri
        return $booking->user_id === $user->id;
    }

    /**
     * Menentukan apakah user bisa membuat booking (Penyewa).
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::CreateBooking->value);
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
     * Menentukan apakah user (Perental / Driver / Admin) bisa mengubah status booking.
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->hasPermissionTo(Permission::UpdateBookingStatus->value);
    }
}