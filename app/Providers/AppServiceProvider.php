<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;                    // ✅ Tambahkan import model User
use App\Models\Car;                     // ✅ Tambahkan import model Car
use App\Models\Booking;                 // ✅ Tambahkan import model Booking
use App\Enums\Role;                     // ✅ Tambahkan import Enum Role
use App\Policies\UserProfilePolicy;     // ✅ Tambahkan import Policy User
use App\Policies\CarPolicy;             // ✅ Tambahkan import Policy Car
use App\Policies\BookingPolicy;         // ✅ Tambahkan import Policy Booking

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
    }
}