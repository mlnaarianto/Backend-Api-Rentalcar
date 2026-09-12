<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    protected UserProfileService $userProfileService;

    public function __construct(UserProfileService $userProfileService)
    {
        $this->userProfileService = $userProfileService;
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // 🛡️ Otorisasi menggunakan Policy (Memanggil method update di UserProfilePolicy)
        $this->authorize('update', $user);

        $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'phone'            => [
                'nullable',
                'string',
                'regex:/^\+[1-9]\d{6,14}$/',
                // Nomor HP harus unik di tabel personal_data,
                // tapi abaikan baris milik user yang sedang update sendiri.
                Rule::unique('personal_data', 'phone')->ignore($user->id, 'user_id'),
            ],
            'birth_date'       => 'nullable|date',
            'address'          => 'nullable|string',
            'avatar'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'ktp'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'sim_number'       => [
                'nullable',
                'string',
                'max:50',
                // 🟢 TAMBAHAN: nomor SIM juga harus unik per akun,
                // abaikan baris milik user yang sedang update sendiri.
                Rule::unique('personal_data', 'sim_number')->ignore($user->id, 'user_id'),
            ],
            'sim_type'         => 'nullable|string|max:10',
            'sim_expired_date' => 'nullable|date',
            'sim_image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'phone.regex'       => 'Format nomor HP harus diawali kode negara, contoh: +6281234567890',
            'phone.unique'      => 'Nomor HP ini sudah digunakan oleh akun lain',
            'sim_number.unique' => 'Nomor SIM ini sudah digunakan oleh akun lain',
        ]);

        try {
            $updatedUser = $this->userProfileService->update($user, $request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Profil berhasil diperbarui',
                'data'    => $updatedUser
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update Profile Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui profil: ' . $e->getMessage()
            ], 500);
        }
    }
}