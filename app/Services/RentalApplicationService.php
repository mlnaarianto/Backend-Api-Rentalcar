<?php

namespace App\Services;

use App\Models\RentalApplication;
use Illuminate\Http\Request;
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
     * Proses simpan atau update pengajuan rental dengan validasi kelengkapan Personal Data
     */
    public function processStoreOrUpdate(Request $request, $user)
    {
        // 1. Pastikan relasi personalData ada dan field pentingnya sudah terisi
        $personalData = $user->personalData;

        if (!$personalData || 
            empty($personalData->phone) || 
            empty($personalData->ktp_image) || 
            empty($personalData->sim_number) || 
            empty($personalData->sim_image)) {
            
            // Lempar error validasi jika data personal belum lengkap
            throw ValidationException::withMessages([
                'personal_data' => 'Anda harus melengkapi Data Personal, foto KTP, dan foto SIM terlebih dahulu di menu Profil sebelum mengajukan diri sebagai perental.'
            ]);
        }

        // 2. Jika sudah lengkap, simpan atau update pengajuan rental
        return RentalApplication::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $request->business_name,
                'business_address' => $request->business_address,
                'status' => 'pending', // Reset status jadi pending jika memperbarui data
                'admin_notes' => null,
            ]
        );
    }
}