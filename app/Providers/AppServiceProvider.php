<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;                    // ✅ Tambahkan import model User
use App\Models\Car;                     // ✅ Tambahkan import model Car
use App\Models\Booking;                 // ✅ Tambahkan import model Booking
use App\Models\Notification;            // ✅ Tambahkan import model Notification
use App\Enums\Role;                     // ✅ Tambahkan import Enum Role
use App\Policies\UserProfilePolicy;     // ✅ Tambahkan import Policy User
use App\Policies\CarPolicy;             // ✅ Tambahkan import Policy Car
use App\Policies\BookingPolicy;         // ✅ Tambahkan import Policy Booking
use App\Observers\NotificationObserver; // ✅ Tambahkan import Observer Notification

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole(Role::SuperAdmin->value) ? true : null;
        });

        // ✅ Daftarkan Policy di sini tanpa hardcode string role
        Gate::policy(User::class, UserProfilePolicy::class);
        Gate::policy(Car::class, CarPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class); // 👈 Daftarkan Policy Booking

        // 🔴 Daftarkan Observer Notification — supaya setiap kali
        // Notification::create() dipanggil di manapun (BookingService,
        // dst), event broadcast otomatis terpicu tanpa perlu ubah
        // kode di titik-titik pembuatan notifikasi itu sendiri.
        Notification::observe(NotificationObserver::class);
    }
}