<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalApplication;
use App\Services\RentalApplicationService;
use Illuminate\Http\Request;

class RentalApplicationController extends Controller
{
    protected $rentalService;

    public function __construct(RentalApplicationService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

    /**
     * Endpoint GET: Ambil status pengajuan
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $application = $this->rentalService->getApplicationByUser($user);

        if (!$application) {
            return response()->json([
                'status' => 'success',
                'message' => 'Belum ada pengajuan perental.',
                'data' => null
            ], 200);
        }

        $this->authorize('view', $application);

        return response()->json([
            'status' => 'success',
            'message' => 'Data pengajuan ditemukan.',
            'data' => $application
        ], 200);
    }

    /**
     * Endpoint GET: Reverse geocode koordinat -> alamat.
     * Dipanggil client tiap kali user klik/pindah marker di peta, untuk auto-fill alamat.
     */
    public function reverseGeocode(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'latitude.required' => 'Koordinat latitude wajib diisi.',
            'longitude.required' => 'Koordinat longitude wajib diisi.',
        ]);

        $address = $this->rentalService->reverseGeocode(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        return response()->json([
            'status' => 'success',
            'message' => $address ? 'Alamat ditemukan.' : 'Alamat tidak ditemukan, silakan isi manual.',
            'data' => [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'formatted_address' => $address,
            ],
        ], 200);
    }

    /**
     * Endpoint POST: Kirim atau update pengajuan
     */
    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string|max:500',
            'formatted_address' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'latitude.required' => 'Silakan tandai lokasi usaha Anda di peta.',
            'longitude.required' => 'Silakan tandai lokasi usaha Anda di peta.',
        ]);

        $user = $request->user();
        $application = $this->rentalService->getApplicationByUser($user);

        if ($application) {
            $this->authorize('update', $application);
        }

        $updatedOrNewApplication = $this->rentalService->processStoreOrUpdate($request, $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan perental berhasil dikirim dan sedang menunggu verifikasi admin.',
            'data' => $updatedOrNewApplication
        ], 200);
    }
}