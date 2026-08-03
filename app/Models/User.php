<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

use Spatie\Permission\Traits\HasRoles; 

#[Fillable(['google_id', 'name', 'email', 'password', 'avatar', 'login_type', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function personalData(): HasOne
    {
        return $this->hasOne(PersonalData::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    /**
     * Relasi ke booking-booking yang dibuat user (sebagai Penyewa)
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}