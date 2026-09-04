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

        // Cek apakah user punya hak akses melihat semua mobil secara global
        // (Misalnya Super Admin atau user yang dibekali permission view-all-cars)
        if ($user && ($user->hasRole('Super Admin') || $user->can('view-all-cars'))) {
            return Car::with('user.personalData')->latest()->get();
        }

        // Jika perental biasa, hanya tampilkan mobil miliknya sendiri
        if ($user && $user->hasRole('Perental')) {
            return $user->cars()->with('user.personalData')->latest()->get();
        }

        // Default untuk penyewa/driver atau umum (menampilkan semua mobil yang tersedia untuk disewa)
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
            $this->deleteImageFile($car);

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
        $this->deleteImageFile($car);

        return $car->delete();
    }

    /**
     * Hapus file foto mobil dari disk (jika ada & bukan URL eksternal).
     *
     * FIX: sebelumnya kode ini mengecek $car->image (hasil ACCESSOR, yang
     * sudah diubah jadi full URL oleh Model::image()). Akibatnya
     * filter_var($car->image, FILTER_VALIDATE_URL) hampir selalu TRUE,
     * jadi file lama nggak pernah kehapus dari storage (jadi sampah).
     *
     * Sekarang pakai getRawOriginal('image') -> path asli di DB
     * (mis. "cars/abc123.jpg"), bukan full URL.
     */
    protected function deleteImageFile(Car $car): void
    {
        $rawPath = $car->getRawOriginal('image');

        if (! $rawPath) {
            return;
        }

        // Kalau raw value ternyata URL eksternal (bukan path lokal), jangan dihapus.
        if (filter_var($rawPath, FILTER_VALIDATE_URL)) {
            return;
        }

        if (Storage::disk('public')->exists($rawPath)) {
            Storage::disk('public')->delete($rawPath);
        }
    }
}