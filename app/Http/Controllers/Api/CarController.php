<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class CarController extends Controller
{
    protected CarService $carService;

    public function __construct(CarService $carService)
    {
        $this->carService = $carService;
    }

    /**
     * Tampilkan semua daftar mobil
     */
    public function index()
    {
        try {
            $this->authorize('viewAny', Car::class);

            $cars = $this->carService->getAllCars();

            return response()->json([
                'status' => 'success',
                'data'   => $cars,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat data mobil: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tambah mobil baru (Biasanya untuk Admin / Perental)
     */
    public function store(Request $request)
    {
        $this->authorize('create', Car::class);

        $request->validate([
            'name'                  => 'required|string|max:255',
            'brand'                 => 'required|string|max:255',
            'plate_number'          => 'required|string|unique:cars,plate_number',
            'engine_type'           => 'required|string',
            'fuel_spec'             => 'nullable|string',
            'seats'                 => 'required|integer',
            'year'                  => 'required|digits:4',
            'price_per_day'         => 'required|numeric',
            'driver_price_per_day'  => 'nullable|numeric', // 👈 Ditambahkan validasi tarif driver
            'description'           => 'nullable|string',
            'image'                 => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status'                => 'nullable|in:tersedia,disewa,perbaikan',
        ]);

        try {
            $data = $request->except('image');
            $data['user_id'] = Auth::id(); 

            $car = $this->carService->createCar(
                $data,
                $request->file('image')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Mobil berhasil ditambahkan',
                'data'    => $car,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menambahkan mobil: ' . $e->getMessage(),
            ], 500);
        }
    }

   /**
     * Detail spesifik mobil
     */
    public function show($id)
    {
        try {
            $car = Car::with('user.personalData')->findOrFail($id);
            
            $this->authorize('view', $car);

            return response()->json([
                'status' => 'success',
                'data'   => $car,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mobil tidak ditemukan atau akses ditolak',
            ], 404);
        }
    }

    /**
     * Perbarui data mobil
     */
    public function update(Request $request, $id)
    {
        try {
            $car = Car::findOrFail($id);

            $this->authorize('update', $car);

            $request->validate([
                'name'                  => 'sometimes|required|string|max:255',
                'brand'                 => 'sometimes|required|string|max:255',
                'plate_number'          => 'sometimes|required|string|unique:cars,plate_number,' . $car->id,
                'engine_type'           => 'sometimes|required|string',
                'fuel_spec'             => 'nullable|string',
                'seats'                 => 'sometimes|required|integer',
                'year'                  => 'sometimes|required|digits:4',
                'price_per_day'         => 'sometimes|required|numeric',
                'driver_price_per_day'  => 'nullable|numeric', // 👈 Ditambahkan validasi update tarif driver
                'description'           => 'nullable|string',
                'image'                 => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'status'                => 'nullable|in:tersedia,disewa,perbaikan',
            ]);

            $updatedCar = $this->carService->updateCar(
                $car,
                $request->except('image'),
                $request->file('image')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Data mobil berhasil diperbarui',
                'data'    => $updatedCar,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui mobil: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus mobil
     */
    public function destroy($id)
    {
        try {
            $car = Car::findOrFail($id);

            $this->authorize('delete', $car);

            $this->carService->deleteCar($car);

            return response()->json([
                'status'  => 'success',
                'message' => 'Mobil berhasil dihapus',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus mobil: ' . $e->getMessage(),
            ], 500);
        }
    }
}