<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;                    // ✅ Tambahkan import model User
use App\Models\Car;                     // ✅ Tambahkan import model Car
use App\Policies\UserProfilePolicy;     // ✅ Tambahkan import Policy User
use App\Policies\CarPolicy;             // ✅ Tambahkan import Policy Car

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
            return $user->hasRole('Super Admin') ? true : null;
        });

        // ✅ Daftarkan Policy di sini
        Gate::policy(User::class, UserProfilePolicy::class);
        Gate::policy(Car::class, CarPolicy::class); // 👈 Daftarkan Policy Car
    }
}