<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}