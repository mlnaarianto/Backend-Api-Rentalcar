<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'name'       => 'sometimes|required|string|max:255', // 👈 Diubah menjadi 'sometimes' agar aman jika tidak dikirim dari Flutter
            'phone'      => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'address'    => 'nullable|string',
            'avatar'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'ktp'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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