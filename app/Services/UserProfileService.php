<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    public function update(User $user, Request $request): User
    {
        // 1. Update tabel users (Name & Avatar hanya diupdate jika dikirim dari request)
        $userData = [];

        if ($request->filled('name')) {
            $userData['name'] = $request->name;
        }

        if ($request->hasFile('avatar')) {
            // Hapus avatar lama kalau ada (sekarang tinggal path relatif, tidak perlu parse URL lagi)
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Simpan PATH RELATIF saja, bukan full URL — accessor di model yang urus generate URL-nya
            $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Hanya jalankan query update jika ada data user yang berubah
        if (!empty($userData)) {
            $user->update($userData);
        }

        // 2. Update tabel personal_data (Phone, Birth Date, Address, KTP, & SIM)
        $personalDataPayload = [
            'phone'            => $request->phone,
            'birth_date'       => $request->birth_date,
            'address'          => $request->address,
            'sim_number'       => $request->sim_number,
            'sim_type'         => $request->sim_type,
            'sim_expired_date' => $request->sim_expired_date,
        ];

        $personalData = $user->personalData;

        // Handle upload KTP image
        if ($request->hasFile('ktp')) {
            if ($personalData && $personalData->ktp_image) {
                Storage::disk('public')->delete($personalData->ktp_image);
            }

            // Simpan path relatif aja, misal: "ktp_images/xxxx.jpg"
            $personalDataPayload['ktp_image'] = $request->file('ktp')->store('ktp_images', 'public');
        }

        // Handle upload SIM image
        if ($request->hasFile('sim_image')) {
            if ($personalData && $personalData->sim_image) {
                Storage::disk('public')->delete($personalData->sim_image);
            }

            $personalDataPayload['sim_image'] = $request->file('sim_image')->store('sim_images', 'public');
        }

        $user->personalData()->updateOrCreate(
            ['user_id' => $user->id],
            $personalDataPayload
        );

        return $user->fresh()->load('personalData');
    }
}