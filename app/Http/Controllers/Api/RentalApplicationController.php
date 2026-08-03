<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalApplication;
use App\Services\RentalApplicationService;
use Illuminate\Http\Request;

class RentalApplicationController extends Controller
{
    protected $rentalService;

    // Inject Service melalui Constructor
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

        // Jalankan policy 'view' untuk memastikan user berhak melihat data ini
        $this->authorize('view', $application);

        return response()->json([
            'status' => 'success',
            'message' => 'Data pengajuan ditemukan.',
            'data' => $application
        ], 200);
    }

    /**
     * Endpoint POST: Kirim atau update pengajuan
     */
    public function storeOrUpdate(Request $request)
    {
        // Validasi input disederhanakan hanya untuk data usaha
        $request->validate([
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string',
        ]);

        $user = $request->user();
        $application = $this->rentalService->getApplicationByUser($user);

        // Jika data pengajuan sudah ada dan ingin di-update, cek policy 'update'-nya
        if ($application) {
            $this->authorize('update', $application);
        }

        // Panggil logika dari service
        $updatedOrNewApplication = $this->rentalService->processStoreOrUpdate($request, $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan perental berhasil dikirim dan sedang menunggu verifikasi admin.',
            'data' => $updatedOrNewApplication
        ], 200);
    }
}