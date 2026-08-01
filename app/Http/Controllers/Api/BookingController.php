<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

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
    public function index()
    {
        try {
            $this->authorize('viewAny', Booking::class);

            $bookings = $this->bookingService->getBookingsForUser();

            return response()->json([
                'status' => 'success',
                'data'   => $bookings,
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki izin melihat daftar booking.',
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat data booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buat pemesanan baru (Penyewa)
     */
    public function store(Request $request)
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

            return response()->json([
                'status'  => 'success',
                'message' => 'Booking berhasil dibuat. Silakan lakukan pembayaran.',
                'data'    => $booking->load(['car', 'user', 'driver.personalData']),
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki izin membuat booking.',
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat booking: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Detail spesifik booking
     */
    public function show($id)
    {
        try {
            $booking = Booking::with(['user.personalData', 'car', 'driver.personalData'])->findOrFail($id);
            
            $this->authorize('view', $booking);

            return response()->json([
                'status' => 'success',
                'data'   => $booking,
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Anda tidak berhak melihat detail booking ini.',
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }
    }

    /**
     * Batalkan booking (Penyewa / Pemilik)
     */
    public function cancel($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            $this->authorize('cancel', $booking);

            $updatedBooking = $this->bookingService->cancelBooking($booking);

            return response()->json([
                'status'  => 'success',
                'message' => 'Booking berhasil dibatalkan.',
                'data'    => $updatedBooking,
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Anda tidak diizinkan membatalkan booking ini.',
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membatalkan booking: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Perbarui status booking / status pembayaran
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            $this->authorize('updateStatus', $booking);

            // Ubah 'required' menjadi 'sometimes' agar bisa memperbarui salah satu saja (misal hanya payment_status)
            $request->validate([
                'status'         => 'sometimes|in:pending,confirmed,active,completed,cancelled',
                'payment_status' => 'sometimes|in:unpaid,paid,refunded',
            ]);

            $updatedBooking = $this->bookingService->updateBookingStatus($booking, $request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Status berhasil diperbarui.',
                'data'    => $updatedBooking,
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki izin mengubah status booking.',
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ambil daftar user yang memiliki Role 'Driver'
     */
    public function getAvailableDrivers()
    {
        try {
            $drivers = User::role('Driver')->with('personalData')->get();

            return response()->json([
                'status' => 'success',
                'data'   => $drivers,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat daftar driver: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tugaskan / Assign Driver ke Booking tertentu
     */
    public function assignDriver(Request $request, $id)
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

            return response()->json([
                'status'  => 'success',
                'message' => 'Driver berhasil ditugaskan ke pemesanan ini.',
                'data'    => $updatedBooking,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menugaskan driver: ' . $e->getMessage(),
            ], 500);
        }
    }
}