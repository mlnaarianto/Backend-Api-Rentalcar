<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
    'driver_price_per_day', // 👈 Ditambahkan ke fillable
    'description',
    'image',
    'video_url',
    'status'
])]
#[Hidden([])]
class Car extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'driver_price_per_day' => 'decimal:2', // 👈 Ditambahkan cast decimal:2
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
     * Accessor untuk menghasilkan URL lengkap gambar mobil (jika disimpan di storage).
     */
    protected function image(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? (filter_var($value, FILTER_VALIDATE_URL) ? $value : Storage::url($value)) : null,
        );
    }
}