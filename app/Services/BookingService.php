<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

class BookingService
{
    /**
     * Ambil daftar booking berdasarkan Role user yang sedang login
     */
    public function getBookingsForUser()
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return Booking::with(['user.personalData', 'car.user', 'driver.personalData'])->latest()->get();
        }

        if ($user->hasRole('Perental')) {
            return Booking::with(['user.personalData', 'car.user', 'driver.personalData'])
                ->whereHas('car', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->latest()->get();
        }

        if ($user->hasRole('Driver')) {
            return Booking::with(['user.personalData', 'car.user', 'driver.personalData'])
                ->where('driver_id', $user->id)->latest()->get();
        }

        // Default untuk Penyewa (Customer)
        return Booking::with(['user.personalData', 'car.user', 'driver.personalData'])
            ->where('user_id', $user->id)->latest()->get();
    }

    /**
     * Proses pembuatan booking baru dengan driver dinamis & metode pembayaran
     */
    public function createBooking(array $data): Booking
    {
        $user = Auth::user();
        $personalData = $user->personalData;

        // Validasi ekstra di Service layer untuk memastikan KTP & SIM lengkap
        if (!$personalData || empty($personalData->ktp_image)) {
            throw new Exception('Anda wajib melengkapi Foto KTP terlebih dahulu.');
        }

        if (empty($personalData->sim_number) || empty($personalData->sim_image)) {
            throw new Exception('Anda wajib melengkapi Nomor dan Foto SIM terlebih dahulu sebelum melakukan pemesanan.');
        }

        $car = Car::findOrFail($data['car_id']);

        // Cek status ketersediaan mobil
        if ($car->status !== 'tersedia') {
            throw new Exception('Maaf, mobil ini sedang tidak tersedia untuk disewa.');
        }

        // Hitung durasi sewa
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1; // Minimal 1 hari

        $withDriver = $data['with_driver'] ?? false;

        // Ambil tarif driver secara dinamis dari data mobil (tidak hardcode)
        $driverFeePerDay = $withDriver ? ($car->driver_price_per_day ?? 0.00) : 0.00;

        $totalCarPrice = $totalDays * $car->price_per_day;
        $totalDriverFee = $totalDays * $driverFeePerDay;
        $totalPrice = $totalCarPrice + $totalDriverFee;

        $booking = Booking::create([
            'user_id'            => $user->id,
            'car_id'             => $car->id,
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'total_days'         => $totalDays,
            'price_per_day'      => $car->price_per_day,
            'with_driver'        => $withDriver,
            'driver_fee_per_day' => $driverFeePerDay,
            'total_driver_fee'   => $totalDriverFee,
            'total_price'        => $totalPrice,
            'status'             => 'pending',
            'payment_status'     => 'unpaid',
            'payment_method'     => $data['payment_method'], // qris atau cod
            'notes'              => $data['notes'] ?? null,
        ]);

        // 🔔 Kirim Notifikasi ke Perental (Pemilik Mobil)
        if ($car->user_id) {
            Notification::create([
                'user_id' => $car->user_id,
                'title'   => 'Pesanan Baru Masuk!',
                'message' => 'Ada penyewa yang memesan mobil ' . $car->name . ' untuk tanggal ' . $data['start_date'] . '.',
                'type'    => 'booking',
            ]);
        }

        return $booking;
    }

    /**
     * Batalkan booking
     */
    public function cancelBooking(Booking $booking): Booking
    {
        if (in_array($booking->status, ['completed', 'cancelled'])) {
            throw new Exception('Booking sudah selesai atau sudah dibatalkan sebelumnya.');
        }

        $booking->update(['status' => 'cancelled']);

        // 🔔 Kirim Notifikasi ke Penyewa bahwa pesanan dibatalkan
        Notification::create([
            'user_id' => $booking->user_id,
            'title'   => 'Pesanan Dibatalkan',
            'message' => 'Pesanan untuk mobil ' . ($booking->car->name ?? 'Rental') . ' telah dibatalkan.',
            'type'    => 'booking_cancelled',
        ]);

        return $booking->load(['car', 'driver.personalData', 'user']);
    }

    /**
     * Perbarui status booking atau status pembayaran secara fleksibel
     */
    public function updateBookingStatus(Booking $booking, array $data): Booking
    {
        if (isset($data['status'])) {
            $booking->update(['status' => $data['status']]);

            if ($data['status'] === 'active') {
                $booking->car()->update(['status' => 'disewa']);
            } elseif ($data['status'] === 'completed' || $data['status'] === 'cancelled') {
                $booking->car()->update(['status' => 'tersedia']);
            }

            // 🔔 Kirim Notifikasi ke Penyewa
            Notification::create([
                'user_id' => $booking->user_id,
                'title'   => 'Status Pesanan Diperbarui',
                'message' => 'Status pesanan mobil ' . ($booking->car->name ?? '') . ' kini menjadi: ' . strtoupper($data['status']),
                'type'    => 'booking_status',
            ]);
        }

        if (isset($data['payment_status'])) {
            $booking->update(['payment_status' => $data['payment_status']]);

            if ($data['payment_status'] === 'paid') {
                Notification::create([
                    'user_id' => $booking->user_id,
                    'title'   => 'Pembayaran Berhasil',
                    'message' => 'Pembayaran untuk pesanan mobil ' . ($booking->car->name ?? '') . ' telah dikonfirmasi LUNAS.',
                    'type'    => 'payment',
                ]);
            }
        }

        return $booking->fresh(['car', 'driver.personalData', 'user']);
    }

    /**
     * Tugaskan Driver ke Booking tertentu
     */
    public function assignDriverToBooking(Booking $booking, int $driverId): Booking
    {
        $booking->update([
            'driver_id' => $driverId,
        ]);

        $loadedBooking = $booking->load(['car', 'driver.personalData', 'user']);

        if ($loadedBooking->driver && $loadedBooking->driver->name) {
            Notification::create([
                'user_id' => $booking->user_id,
                'title'   => 'Driver Ditugaskan',
                'message' => 'Driver ' . $loadedBooking->driver->name . ' telah ditugaskan untuk perjalanan Anda.',
                'type'    => 'driver_assignment',
            ]);
        }

        return $loadedBooking;
    }
}