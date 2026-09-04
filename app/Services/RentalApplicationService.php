<?php

namespace App\Services;

use App\Models\RentalApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RentalApplicationService
{
    /**
     * Ambil data pengajuan berdasarkan user
     */
    public function getApplicationByUser($user)
    {
        return RentalApplication::with('user.personalData')->where('user_id', $user->id)->first();
    }

    /**
     * Proses simpan atau update pengajuan rental
     */
    public function processStoreOrUpdate(Request $request, $user)
    {
        $personalData = $user->personalData;

        if (
            !$personalData ||
            empty($personalData->phone) ||
            empty($personalData->ktp_image) ||
            empty($personalData->sim_number) ||
            empty($personalData->sim_image)
        ) {
            throw ValidationException::withMessages([
                'personal_data' => 'Anda harus melengkapi Data Personal, foto KTP, dan foto SIM terlebih dahulu di menu Profil sebelum mengajukan diri sebagai perental.'
            ]);
        }

        $data = [
            'business_name' => $request->business_name,
            'business_address' => $request->business_address,
            'status' => 'pending',
            'admin_notes' => null,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        $data['formatted_address'] = $request->filled('formatted_address')
            ? $request->formatted_address
            : $this->reverseGeocode($request->latitude, $request->longitude);

        return RentalApplication::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
    }

    /**
     * [ADMIN] Ambil daftar semua pengajuan, opsional difilter berdasarkan status
     */
    public function getAllApplications(?string $status = null)
    {
        return RentalApplication::with('user.personalData')
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15);
    }

    /**
     * [ADMIN] Ambil satu pengajuan berdasarkan ID
     */
    public function getApplicationById(int $id): ?RentalApplication
    {
        return RentalApplication::with('user.personalData')->find($id);
    }

    /**
     * [ADMIN] Approve atau reject pengajuan
     */
    public function verifyApplication(RentalApplication $application, string $status, ?string $adminNotes = null): RentalApplication
    {
        $application->update([
            'status' => $status,
            'admin_notes' => $adminNotes,
        ]);

        return $application->fresh('user.personalData');
    }

    /**
     * Reverse geocode: koordinat -> nama tempat, menggunakan Nominatim (OpenStreetMap)
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        $cacheKey = sprintf('reverse-geocode:%s,%s', round($lat, 5), round($lng, 5));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($lat, $lng) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'RentalCarApplication/1.0 (maulana.arianto@polibatam.ac.id)',
                        'Referer' => config('app.url'),
                    ])
                    ->timeout(10)
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'lat' => $lat,
                        'lon' => $lng,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'accept-language' => 'id',
                    ]);

                if (!$response->successful()) {
                    Log::warning('Reverse geocoding gagal (HTTP error)', [
                        'lat' => $lat,
                        'lng' => $lng,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return null;
                }

                $result = $response->json();

                return $result['display_name'] ?? null;
            } catch (\Throwable $e) {
                Log::error('Reverse geocoding error', [
                    'lat' => $lat,
                    'lng' => $lng,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
}
