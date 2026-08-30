<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'car_id',
    'driver_id',
    'start_date',
    'end_date',
    'total_days',
    'price_per_day',
    'with_driver',
    'driver_fee_per_day',
    'total_driver_fee',
    'total_price',
    'status',
    'payment_status',
    'payment_method',
    'midtrans_order_id',   // 👈 Ditambahkan
    'qris_url',            // 👈 Ditambahkan
    'payment_expired_at',  // 👈 Ditambahkan
    'notes'
])]
#[Hidden([])]
class Booking extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'car_id' => 'integer',
            'driver_id' => 'integer',
            'total_days' => 'integer',
            'price_per_day' => 'decimal:2',
            'driver_fee_per_day' => 'decimal:2',
            'total_driver_fee' => 'decimal:2',
            'total_price' => 'decimal:2',
            'with_driver' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'payment_expired_at' => 'datetime', // 👈 Ditambahkan
        ];
    }

    /**
     * Relasi ke model User (Penyewa mobil)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model Car (Mobil yang disewa)
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Relasi ke model User (Driver/Sopir yang ditugaskan, jika ada)
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}