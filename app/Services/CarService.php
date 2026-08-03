<?php

namespace App\Services;

use App\Models\Car;
use App\Enums\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CarService
{
    /**
     * Ambil semua data mobil (Disesuaikan dengan role user yang login)
     */
    public function getAllCars()
    {
        $user = Auth::user();

        if ($user && $user->hasRole(Role::Perental->value)) {
            return $user->cars()->with('user.personalData')->latest()->get();
        }

        return Car::with('user.personalData')->latest()->get();
    }

    /**
     * Simpan mobil baru
     */
    public function createCar(array $data, ?object $imageFile = null): Car
    {
        if ($imageFile) {
            $data['image'] = $imageFile->store('cars', 'public');
        }

        // Pastikan input driver_price_per_day memiliki nilai default jika kosong
        if (!isset($data['driver_price_per_day'])) {
            $data['driver_price_per_day'] = 0;
        }

        return Car::create($data);
    }

    /**
     * Update data mobil
     */
    public function updateCar(Car $car, array $data, ?object $imageFile = null): Car
    {
        if ($imageFile) {
            if ($car->image && !filter_var($car->image, FILTER_VALIDATE_URL)) {
                $oldPath = str_replace('/storage/', '', $car->image);
                Storage::disk('public')->delete($oldPath);
            }

            $data['image'] = $imageFile->store('cars', 'public');
        }

        $car->update($data);
        return $car;
    }

    /**
     * Hapus mobil
     */
    public function deleteCar(Car $car): bool
    {
        if ($car->image && !filter_var($car->image, FILTER_VALIDATE_URL)) {
            $oldPath = str_replace('/storage/', '', $car->image);
            Storage::disk('public')->delete($oldPath);
        }

        return $car->delete();
    }
}
