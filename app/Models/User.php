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

    /**
     * Relasi ke pengajuan perental (Rental Application) milik user ini
     */
    public function rentalApplication(): HasOne
    {
        return $this->hasOne(RentalApplication::class);
    }

    /**
     * Relasi ke pesan-pesan yang dikirim oleh user ini
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Relasi ke chat room yang pernah dikelola sebagai last sender
     */
    public function chatsAsLastSender(): HasMany
    {
        return $this->hasMany(Chat::class, 'last_sender_id');
    }

    /**
     * Relasi ke token-token FCM milik user ini (multi-device)
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(UserFcmToken::class);
    }
}