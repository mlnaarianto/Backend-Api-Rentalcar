<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;             // ✅ Import Facade Event
use App\Models\User;                              // ✅ Import model User
use App\Models\Car;                               // ✅ Import model Car
use App\Models\Booking;                           // ✅ Import model Booking
use App\Models\Notification;                      // ✅ Import model Notification
use App\Models\RentalApplication;                 // ✅ Import model RentalApplication
use App\Enums\Role;                               // ✅ Import Enum Role
use App\Policies\UserProfilePolicy;               // ✅ Import Policy User
use App\Policies\CarPolicy;                       // ✅ Import Policy Car
use App\Policies\BookingPolicy;                   // ✅ Import Policy Booking
use App\Policies\RentalApplicationPolicy;         // ✅ Import Policy RentalApplication
use App\Observers\NotificationObserver;           // ✅ Import Observer Notification

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
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(RentalApplication::class, RentalApplicationPolicy::class); // 👈 Daftarkan Policy RentalApplication

        // 🔴 Daftarkan Observer Notification — supaya setiap kali
        // Notification::create() dipanggil di manapun (BookingService,
        // dst), event broadcast otomatis terpicu tanpa perlu ubah
        // kode di titik-titik pembuatan notifikasi itu sendiri.
        Notification::observe(NotificationObserver::class);

        // 🟢 CATATAN: SendPushNotification TIDAK didaftarkan manual di sini.
        // Laravel (11+) otomatis mendeteksi listener di app/Listeners yang
        // method handle()-nya type-hint ke App\Events\NotificationCreated.
        // Mendaftarkan manual lewat Event::listen() di sini akan membuatnya
        // terpanggil DOBEL untuk setiap 1 event yang sama.
    }
}