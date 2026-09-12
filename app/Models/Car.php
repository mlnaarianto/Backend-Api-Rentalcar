<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable([
    'user_id',
    'name',
    'brand',
    'plate_number',
    'engine_type',
    'fuel_spec',
    'seats',
    'year',
    'price_per_day',
    'driver_price_per_day',
    'description',
    'image',
    'video_url',
    'status',
])]
#[Hidden([])]
class Car extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'driver_price_per_day' => 'decimal:2',
            'year' => 'integer',
            'seats' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Relasi ke model User (Pemilik/Perental mobil)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor URL lengkap gambar mobil dengan Dynamic Host Detection.
     * - Jika diakses via Filament/Admin (browser laptop), menyesuaikan host aktif.
     * - Jika diakses via API/Flutter, menggunakan IP lokal server agar HP fisik terbaca.
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) {
                    return null;
                }

                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }

                return Storage::disk('public')->url($value);
            }
        );
    }

    /**
     * Helper ambil path RAW (bukan full URL) dari kolom image.
     * Dipakai internal di Filament EditCar supaya FileUpload tidak error.
     */
    public function getRawImagePath(): ?string
    {
        return $this->getRawOriginal('image');
    }
}
