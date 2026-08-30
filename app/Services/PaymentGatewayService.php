<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentGatewayService
{
    protected string $serverKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->baseUrl = config('services.midtrans.is_production')
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    /**
     * Buat transaksi QRIS baru untuk sebuah booking.
     */
    public function createQrisTransaction(Booking $booking): Booking
    {
        $orderId = 'BOOKING-' . $booking->id . '-' . time();

        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name ?? 'Penyewa',
                'email'      => $booking->user->email ?? 'noemail@example.com',
            ],
        ];

        $response = Http::withBasicAuth($this->serverKey, '')
            ->withHeaders(['Accept' => 'application/json'])
            ->post("{$this->baseUrl}/charge", $payload);

        if (!$response->successful()) {
            Log::error('Midtrans QRIS charge gagal', $response->json() ?? []);
            throw new Exception('Gagal membuat transaksi QRIS: ' . $response->body());
        }

        $data = $response->json();

        $qrisUrl = collect($data['actions'] ?? [])
            ->firstWhere('name', 'generate-qr-code')['url'] ?? null;

        $booking->update([
            'midtrans_order_id'  => $orderId,
            'qris_url'           => $qrisUrl,
            'payment_expired_at' => $data['expiry_time'] ?? now()->addMinutes(15),
        ]);

        return $booking->fresh();
    }

    /**
     * Cek status transaksi terbaru dari Midtrans (dipakai untuk polling manual).
     */
    public function checkTransactionStatus(string $orderId): array
    {
        $response = Http::withBasicAuth($this->serverKey, '')
            ->withHeaders(['Accept' => 'application/json'])
            ->get("{$this->baseUrl}/{$orderId}/status");

        if (!$response->successful()) {
            Log::error('Midtrans cek status gagal', $response->json() ?? []);
            throw new Exception('Gagal mengecek status transaksi: ' . $response->body());
        }

        return $response->json();
    }
}