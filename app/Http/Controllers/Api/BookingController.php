<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Tampilkan daftar booking (Bisa difilter berdasarkan role)
     */
    public function index(): JsonResponse
    {
        try {
            $this->authorize('viewAny', Booking::class);

            $bookings = $this->bookingService->getBookingsForUser();

            return $this->successResponse($bookings);
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Akses ditolak: Anda tidak memiliki izin melihat daftar booking.', 403);
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal memuat data booking: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Buat pemesanan baru (Penyewa)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', Booking::class);

            $validated = $request->validate([
                'car_id'         => 'required|exists:cars,id',
                'start_date'     => 'required|date|after_or_equal:today',
                'end_date'       => 'required|date|after_or_equal:start_date',
                'with_driver'    => 'sometimes|boolean',
                'payment_method' => 'required|in:qris,cod',
                'notes'          => 'nullable|string',
            ]);

            $booking = $this->bookingService->createBooking($validated);

            return $this->successResponse(
                $booking->load(['car', 'user', 'driver.personalData']),
                'Booking berhasil dibuat. Silakan lakukan pembayaran.',
                201
            );
        } catch (AuthorizationException $e) {
            return $this->errorResponse($this->resolveCreateDeniedMessage($request->user()), 403);
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal membuat booking: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Detail spesifik booking
     */
    public function show($id): JsonResponse
    {
        try {
            $booking = Booking::with(['user.personalData', 'car', 'driver.personalData'])->findOrFail($id);

            $this->authorize('view', $booking);

            return $this->successResponse($booking);
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Akses ditolak: Anda tidak berhak melihat detail booking ini.', 403);
        } catch (\Throwable $e) {
            return $this->errorResponse('Booking tidak ditemukan', 404);
        }
    }

    /**
     * Batalkan booking (Penyewa / Pemilik)
     */
    public function cancel($id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);

            $this->authorize('cancel', $booking);

            $updatedBooking = $this->bookingService->cancelBooking($booking);

            return $this->successResponse($updatedBooking, 'Booking berhasil dibatalkan.');
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Akses ditolak: Anda tidak diizinkan membatalkan booking ini.', 403);
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal membatalkan booking: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Perbarui status booking / status pembayaran
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);

            $this->authorize('updateStatus', $booking);

            $request->validate([
                'status'         => 'sometimes|in:pending,confirmed,active,completed,cancelled',
                'payment_status' => 'sometimes|in:unpaid,paid,refunded',
            ]);

            $updatedBooking = $this->bookingService->updateBookingStatus($booking, $request->all());

            return $this->successResponse($updatedBooking, 'Status berhasil diperbarui.');
        } catch (AuthorizationException $e) {
            return $this->errorResponse('Akses ditolak: Anda tidak memiliki izin mengubah status booking.', 403);
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal memperbarui status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Ambil daftar user yang memiliki Role 'Driver'
     */
    public function getAvailableDrivers(): JsonResponse
    {
        try {
            $drivers = User::role('Driver')->with('personalData')->get();

            return $this->successResponse($drivers);
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal memuat daftar driver: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tugaskan / Assign Driver ke Booking tertentu
     */
    public function assignDriver(Request $request, $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);

            $request->validate([
                'driver_id' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = User::find($value);
                        if (!$user || !$user->hasRole('Driver')) {
                            $fail('User yang dipilih bukan merupakan Driver yang valid.');
                        }
                    },
                ],
            ]);

            $updatedBooking = $this->bookingService->assignDriverToBooking($booking, $request->driver_id);

            return $this->successResponse($updatedBooking, 'Driver berhasil ditugaskan ke pemesanan ini.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal menugaskan driver: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tentukan pesan penolakan yang sesuai saat pembuatan booking ditolak Policy.
     */
    private function resolveCreateDeniedMessage(?User $user): string
    {
        $personal = $user?->personalData;

        if (!$personal || empty($personal->phone) || empty($personal->ktp_image) || empty($personal->sim_number) || empty($personal->sim_image)) {
            return 'Akses ditolak. Anda wajib melengkapi Data Personal (No. HP, KTP, dan SIM) terlebih dahulu.';
        }

        return 'Anda tidak dapat membuat booking baru karena masih memiliki pesanan berstatus pending.';
    }

    /**
     * Format response sukses secara konsisten.
     */
    private function successResponse($data, ?string $message = null, int $code = 200): JsonResponse
    {
        $payload = ['status' => 'success', 'data' => $data];

        if ($message) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $code);
    }

    /**
     * Format response error secara konsisten.
     */
    private function errorResponse(string $message, int $code): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message], $code);
    }
}