<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalApplication;
use App\Services\RentalApplicationService;
use Illuminate\Http\Request;

class AdminRentalApplicationController extends Controller
{
    protected $rentalService;

    public function __construct(RentalApplicationService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

    /**
     * GET /api/admin/rental-applications
     * Daftar semua pengajuan, bisa difilter ?status=pending|approved|rejected
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RentalApplication::class);

        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $applications = $this->rentalService->getAllApplications($request->query('status'));

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengajuan berhasil diambil.',
            'data' => $applications,
        ], 200);
    }

    /**
     * GET /api/admin/rental-applications/{id}
     * Detail satu pengajuan (bisa milik user manapun)
     */
    public function show(int $id)
    {
        $application = $this->rentalService->getApplicationById($id);

        if (!$application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $this->authorize('view', $application);

        return response()->json([
            'status' => 'success',
            'message' => 'Data pengajuan ditemukan.',
            'data' => $application,
        ], 200);
    }

    /**
     * PATCH /api/admin/rental-applications/{id}/verify
     * Approve atau reject pengajuan
     */
    public function verify(Request $request, int $id)
    {
        $application = $this->rentalService->getApplicationById($id);

        if (!$application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $this->authorize('verify', RentalApplication::class);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $updatedApplication = $this->rentalService->verifyApplication(
            $application,
            $validated['status'],
            $validated['admin_notes'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status pengajuan berhasil diperbarui.',
            'data' => $updatedApplication,
        ], 200);
    }
}