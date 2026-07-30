<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    public function update(User $user, Request $request): User
    {
        // 1. Update tabel users (Name & Avatar)
        $userData = ['name' => $request->name];

        if ($request->hasFile('avatar')) {
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                $oldPath = str_replace('/storage/', '', parse_url($user->avatar, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $userData['avatar'] = asset('storage/' . $avatarPath);
        }

        $user->update($userData);

        // 2. Update tabel personal_data (Phone, Birth Date, Address, KTP)
        $personalDataPayload = [
            'phone' => $request->phone,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
        ];

        if ($request->hasFile('ktp')) {
            $personalData = $user->personalData;
            
            if ($personalData && $personalData->ktp_image) {
                $oldKtpPath = str_replace('/storage/', '', parse_url($personalData->ktp_image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldKtpPath);
            }

            $ktpPath = $request->file('ktp')->store('ktp_images', 'public');
            $personalDataPayload['ktp_image'] = asset('storage/' . $ktpPath);
        }

        $user->personalData()->updateOrCreate(
            ['user_id' => $user->id],
            $personalDataPayload
        );

        return $user->fresh()->load('personalData');
    }
}