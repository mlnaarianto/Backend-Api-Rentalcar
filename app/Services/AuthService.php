<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Google\Client as GoogleClient;
use Exception;

class AuthService
{
    /**
     * Proses Redirect ke Google OAuth (Web)
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Proses Callback dari Google OAuth (Web)
     */
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        if (!$googleUser->email) {
            throw new Exception('Email tidak ditemukan dari Google');
        }

        $user = $this->createOrUpdateGoogleUser([
            'email'     => $googleUser->email,
            'google_id' => $googleUser->id,
            'name'      => $googleUser->name,
            'avatar'    => $googleUser->avatar,
        ]);

        $tokens = $this->issueTokenPair($user);

        // SEMENTARA: sampai tahap cookie httpOnly dikerjakan.
        // Pakai fragment (#) bukan query (?) supaya token tidak ikut
        // terkirim ke server/log. React membacanya dari window.location.hash.
        return 'http://localhost:3000/dashboard#' . http_build_query([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in'    => $tokens['expires_in'],
        ]);
    }

    /**
     * Proses Verifikasi Token Google untuk Mobile (Flutter)
     */
    public function handleMobileGoogleLogin(string $idToken)
    {
        $client = new GoogleClient();

        $allowedClientIds = [
            config('services.google.client_id'),
            config('services.google.android_client_id'),
        ];

        $payload = null;
        foreach ($allowedClientIds as $clientId) {
            if (!$clientId) continue;
            $client->setClientId($clientId);
            $payload = $client->verifyIdToken($idToken);
            if ($payload) break;
        }

        if (!$payload) {
            Log::warning('Google Mobile Auth: Token tidak valid');
            throw new Exception('Token tidak valid atau kadaluarsa', 401);
        }

        if (empty($payload['email'])) {
            throw new Exception('Email tidak ditemukan dari token Google', 401);
        }

        Log::info('Google User Data: ' . json_encode([
            'email'     => $payload['email'],
            'name'      => $payload['name'] ?? '',
            'google_id' => $payload['sub'],
        ]));

        $user = $this->createOrUpdateGoogleUser([
            'email'     => $payload['email'],
            'google_id' => $payload['sub'],
            'name'      => $payload['name'] ?? '',
            'avatar'    => $payload['picture'] ?? null,
        ]);

        $tokens = $this->issueTokenPair($user);
        Log::info('User logged in successfully: ' . $user->email);

        return array_merge($tokens, [
            'user' => $this->formatUserData($user),
        ]);
    }

    /**
     * Terbitkan pasangan access + refresh token untuk satu sesi (device).
     */
    public function issueTokenPair(User $user, ?string $sessionId = null): array
    {
        $sessionId ??= (string) Str::uuid();

        $accessTtl  = (int) config('sanctum.access_token_ttl', 15);
        $refreshTtl = (int) config('sanctum.refresh_token_ttl', 480);

        $access = $user->createToken(
            "access|{$sessionId}",
            ['access-api'],
            now()->addMinutes($accessTtl)
        );

        $refresh = $user->createToken(
            "refresh|{$sessionId}",
            ['issue-access-token'],
            now()->addMinutes($refreshTtl)
        );

        return [
            'access_token'  => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $accessTtl * 60, // detik
        ];
    }

    /**
     * Rotation: hapus pasangan lama sesi ini, terbitkan pasangan baru.
     */
    public function rotateTokens(User $user, string $sessionId): array
    {
        return DB::transaction(function () use ($user, $sessionId) {
            $user->tokens()
                ->whereIn('name', ["access|{$sessionId}", "refresh|{$sessionId}"])
                ->delete();

            return $this->issueTokenPair($user, $sessionId);
        });
    }

    /**
     * Proses Login Manual (Email & Password)
     *
     * NONAKTIF: Login manual khusus untuk Admin/sistem sudah digantikan
     * oleh panel Filament (punya auth sendiri). Fungsi ini dikomentari
     * total (bukan dihapus) supaya riwayat implementasinya tetap ada
     * kalau suatu saat dibutuhkan lagi.
     */
    // public function handleManualLogin(array $credentials)
    // {
    //     $user = User::where('email', $credentials['email'])->first();

    //     if (!$user) {
    //         throw new Exception('Email atau password salah', 401);
    //     }

    //     if (!in_array($user->login_type, ['system', 'manual'])) {
    //         throw new Exception('Akun ini terdaftar via Google. Silakan login menggunakan Google.', 403);
    //     }

    //     if (!Hash::check($credentials['password'], $user->password)) {
    //         Log::warning('Failed manual login attempt for: ' . $credentials['email']);
    //         throw new Exception('Email atau password salah', 401);
    //     }

    //     $tokens = $this->issueTokenPair($user);
    //     Log::info('System user logged in: ' . $user->email);

    //     return array_merge($tokens, [
    //         'user' => $this->formatUserData($user),
    //     ]);
    // }

    /**
     * Helper: Buat atau Perbarui User Google & Assign Role Default
     */
    private function createOrUpdateGoogleUser(array $data): User
    {
        $userExists = User::where('email', $data['email'])->exists();

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'google_id'         => $data['google_id'],
                'name'              => $data['name'],
                'avatar'            => $data['avatar'],
                'login_type'        => 'google',
                'email_verified_at' => now(),
            ]
        );

        if (!$userExists || $user->roles()->count() === 0) {
            $user->assignRole('Penyewa');
        }

        return $user;
    }

    /**
     * Helper: Format Data User untuk Response JSON
     */
    public function formatUserData(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'login_type'  => $user->login_type,
            'avatar'      => $user->avatar,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }
}