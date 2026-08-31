<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id', 
    'phone', 
    'birth_date', 
    'address', 
    'ktp_image',
    'sim_number',
    'sim_type',
    'sim_expired_date',
    'sim_image'
])]
class PersonalData extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'sim_expired_date' => 'date', // Cast otomatis untuk tanggal kedaluwarsa SIM
        ];
    }

    /**
     * Relasi kembali ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor: generate URL lengkap untuk ktp_image on-the-fly.
     * Kolom di DB cuma simpan path relatif (misal "ktp_images/xxxx.jpg"),
     * jadi URL selalu ikut APP_URL yang aktif SEKARANG, bukan yang aktif
     * pas file diupload. Aman walau IP/domain server berubah-ubah.
     */
    protected function ktpImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Storage::disk('public')->url($value) : null,
        );
    }

    /**
     * Accessor: sama seperti ktp_image, untuk sim_image.
     */
    protected function simImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Storage::disk('public')->url($value) : null,
        );
    }
}