<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'email'  => $googleUser->email,
            'google_id' => $googleUser->id,
            'name'   => $googleUser->name,
            'avatar' => $googleUser->avatar,
        ]);

        $token = $user->createToken('web-token')->plainTextToken;

        return 'http://localhost:3000/dashboard?token=' . $token;
    }

    /**
     * Proses Verifikasi Token Google untuk Mobile (Flutter)
     */
    public function handleMobileGoogleLogin(string $idToken)
    {
        $client = new GoogleClient();

        $allowedClientIds = [
            env('GOOGLE_CLIENT_ID'),
            env('GOOGLE_ANDROID_CLIENT_ID'),
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

        $token = $user->createToken('mobile-token')->plainTextToken;
        Log::info('User logged in successfully: ' . $user->email);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $this->formatUserData($user),
        ];
    }

    /**
     * Proses Login Manual (Email & Password)
     */
    public function handleManualLogin(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            throw new Exception('Email atau password salah', 401);
        }

        if (!in_array($user->login_type, ['system', 'manual'])) {
            throw new Exception('Akun ini terdaftar via Google. Silakan login menggunakan Google.', 403);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            Log::warning('Failed manual login attempt for: ' . $credentials['email']);
            throw new Exception('Email atau password salah', 401);
        }

        $token = $user->createToken('system-token')->plainTextToken;
        Log::info('System user logged in: ' . $user->email);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $this->formatUserData($user),
        ];
    }

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
