<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Notification;
use App\Enums\Permission;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Exception;

class BookingService
{
    /** Relasi yang selalu di-eager-load untuk daftar/detail booking */
    private const DEFAULT_RELATIONS = ['user.personalData', 'car.user', 'driver.personalData'];

    /**
     * Ambil daftar booking berdasarkan Permission user yang sedang login
     */
    public function getBookingsForUser(): Collection
    {
        $user = Auth::user();

        // Super Admin murni (tanpa mobil terikat, bukan Perental) → ambil semua booking
        if ($user->hasPermissionTo(Permission::ManageBookings->value)
            && !$user->cars()->exists()
            && !$user->hasRole('Perental')) {
            return Booking::with(self::DEFAULT_RELATIONS)->latest()->get();
        }

        // Perental (memiliki mobil yang dikelola) → hanya booking untuk mobil miliknya
        if ($user->hasPermissionTo(Permission::ManageBookings->value)) {
            return Booking::with(self::DEFAULT_RELATIONS)
                ->whereHas('car', fn ($query) => $query->where('user_id', $user->id))
                ->latest()
                ->get();
        }

        // Driver → hanya booking yang ditugaskan padanya
        if ($user->hasPermissionTo(Permission::ViewAssignedBooking->value)) {
            return Booking::with(self::DEFAULT_RELATIONS)
                ->where('driver_id', $user->id)
                ->latest()
                ->get();
        }

        // Default: Penyewa → hanya booking miliknya sendiri
        return $user->bookings()->with(self::DEFAULT_RELATIONS)->latest()->get();
    }

    /**
     * Buat booking baru untuk user yang sedang login
     */
    public function createBooking(array $data): Booking
    {
        $user = Auth::user();
        $personalData = $user->personalData;

        if (!$personalData || empty($personalData->ktp_image)) {
            throw new Exception('Anda wajib melengkapi Foto KTP terlebih dahulu.');
        }

        if (empty($personalData->sim_number) || empty($personalData->sim_image)) {
            throw new Exception('Anda wajib melengkapi Nomor dan Foto SIM terlebih dahulu sebelum melakukan pemesanan.');
        }

        $car = Car::findOrFail($data['car_id']);

        if ($car->status !== 'tersedia') {
            throw new Exception('Maaf, mobil ini sedang tidak tersedia untuk disewa.');
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $withDriver = $data['with_driver'] ?? false;
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
            'payment_method'     => $data['payment_method'],
            'notes'              => $data['notes'] ?? null,
        ]);

        $this->notifyIfOwnerExists($car, function () use ($car, $data) {
            return [
                'title'   => 'Pesanan Baru Masuk!',
                'message' => 'Ada penyewa yang memesan mobil ' . $car->name . ' untuk tanggal ' . $data['start_date'] . '.',
                'type'    => 'booking',
            ];
        });

        return $booking;
    }

    public function cancelBooking(Booking $booking): Booking
    {
        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            throw new Exception('Booking sudah selesai atau sudah dibatalkan sebelumnya.');
        }

        $booking->update(['status' => 'cancelled']);

        Notification::create([
            'user_id' => $booking->user_id,
            'title'   => 'Pesanan Dibatalkan',
            'message' => 'Pesanan untuk mobil ' . ($booking->car->name ?? 'Rental') . ' telah dibatalkan.',
            'type'    => 'booking_cancelled',
        ]);

        return $booking->load(['car', 'driver.personalData', 'user']);
    }

    public function updateBookingStatus(Booking $booking, array $data): Booking
    {
        if (isset($data['status'])) {
            $this->applyStatusChange($booking, $data['status']);
        }

        if (isset($data['payment_status'])) {
            $this->applyPaymentStatusChange($booking, $data['payment_status']);
        }

        return $booking->fresh(['car', 'driver.personalData', 'user']);
    }

    public function assignDriverToBooking(Booking $booking, int $driverId): Booking
    {
        $booking->update(['driver_id' => $driverId]);

        $loadedBooking = $booking->load(['car', 'driver.personalData', 'user']);

        if ($loadedBooking->driver?->name) {
            Notification::create([
                'user_id' => $booking->user_id,
                'title'   => 'Driver Ditugaskan',
                'message' => 'Driver ' . $loadedBooking->driver->name . ' telah ditugaskan untuk perjalanan Anda.',
                'type'    => 'driver_assignment',
            ]);
        }

        return $loadedBooking;
    }

    /**
     * Terapkan perubahan status booking + update status mobil terkait + kirim notifikasi.
     */
    private function applyStatusChange(Booking $booking, string $status): void
    {
        $booking->update(['status' => $status]);

        if ($status === 'active') {
            $booking->car()->update(['status' => 'disewa']);
        } elseif (in_array($status, ['completed', 'cancelled'], true)) {
            $booking->car()->update(['status' => 'tersedia']);
        }

        Notification::create([
            'user_id' => $booking->user_id,
            'title'   => 'Status Pesanan Diperbarui',
            'message' => 'Status pesanan mobil ' . ($booking->car->name ?? '') . ' kini menjadi: ' . strtoupper($status),
            'type'    => 'booking_status',
        ]);
    }

    /**
     * Terapkan perubahan status pembayaran + kirim notifikasi jika lunas.
     */
    private function applyPaymentStatusChange(Booking $booking, string $paymentStatus): void
    {
        $booking->update(['payment_status' => $paymentStatus]);

        if ($paymentStatus === 'paid') {
            Notification::create([
                'user_id' => $booking->user_id,
                'title'   => 'Pembayaran Berhasil',
                'message' => 'Pembayaran untuk pesanan mobil ' . ($booking->car->name ?? '') . ' telah dikonfirmasi LUNAS.',
                'type'    => 'payment',
            ]);
        }
    }

    /**
     * Kirim notifikasi ke pemilik mobil jika ada (helper kecil untuk hindari duplikasi null-check).
     */
    private function notifyIfOwnerExists(Car $car, callable $payloadResolver): void
    {
        if (!$car->user_id) {
            return;
        }

        Notification::create(array_merge(
            ['user_id' => $car->user_id],
            $payloadResolver()
        ));
    }
}