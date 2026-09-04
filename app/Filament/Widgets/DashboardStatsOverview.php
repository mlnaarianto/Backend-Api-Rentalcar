<?php

namespace App\Filament\Widgets;

use App\Models\Car;
use App\Models\Booking;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCars = Car::count();
        $availableCars = Car::where('status', 'tersedia')->count();
        $rentedCars = Car::where('status', 'disewa')->count();

        $totalBookings = Booking::count();
        $activeBookings = Booking::whereIn('status', ['confirmed', 'active'])->count();
        $pendingBookings = Booking::where('status', 'pending')->count();

        $totalRevenue = Booking::where('payment_status', 'paid')->sum('total_price');

        $withDriverCount = Booking::where('with_driver', true)->count();

        return [
            Stat::make('Total Mobil', $totalCars)
                ->description($availableCars . ' tersedia · ' . $rentedCars . ' disewa')
                ->color('primary'),

            Stat::make('Total Booking', $totalBookings)
                ->description($activeBookings . ' aktif · ' . $pendingBookings . ' pending')
                ->color('warning'),

            Stat::make('Total Pendapatan', 'Rp ' . number_format((float) $totalRevenue, 0, ',', '.'))
                ->description('Dari booking berstatus lunas')
                ->color('success'),

            Stat::make('Booking dengan Driver', $withDriverCount)
                ->description('Dari total ' . $totalBookings . ' booking')
                ->color('info'),

            Stat::make('Total Perental', User::role('Perental')->count()),

            Stat::make('Total Penyewa', User::role('Penyewa')->count()),
        ];
    }
}