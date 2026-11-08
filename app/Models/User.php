<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['google_id', 'name', 'email', 'password', 'avatar', 'login_type', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Field tambahan yang otomatis ikut di JSON response (API Flutter & React).
     * Klien cukup pakai `avatar_url`, bukan `avatar` (yang isinya path mentah).
     */
    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ===== Accessor: URL avatar lengkap =====

    /**
     * - Avatar dari Google login -> sudah berupa URL (http...), dikembalikan apa adanya.
     * - Avatar hasil upload Filament -> path relatif di disk 'public',
     *   diubah jadi URL lengkap berdasarkan APP_URL.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->avatar) {
                return null;
            }

            return str_starts_with($this->avatar, 'http')
                ? $this->avatar
                : Storage::disk('public')->url($this->avatar);
        });
    }

    // ===== Relasi =====

    public function personalData(): HasOne
    {
        return $this->hasOne(PersonalData::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function rentalApplication(): HasOne
    {
        return $this->hasOne(RentalApplication::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function chatsAsLastSender(): HasMany
    {
        return $this->hasMany(Chat::class, 'last_sender_id');
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function twoFactorAuth(): HasOne
    {
        return $this->hasOne(UserTwoFactorAuth::class);
    }

    // ===== Implementasi HasAvatar (khusus tampilan Filament) =====

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    // ===== Implementasi HasAppAuthentication =====

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->twoFactorAuth?->secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->twoFactorAuth()->updateOrCreate(
            ['user_id' => $this->id],
            ['secret' => $secret],
        );
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    // ===== Implementasi HasAppAuthenticationRecovery =====

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->twoFactorAuth?->recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->twoFactorAuth()->updateOrCreate(
            ['user_id' => $this->id],
            ['recovery_codes' => $codes],
        );
    }
}