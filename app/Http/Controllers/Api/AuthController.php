<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * ========================================================
     * 1. ALUR REDIRECT (Web / React)
     * ========================================================
     */
    public function redirectToGoogle()
    {
        try {
            return $this->authService->redirectToGoogle();
        } catch (Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage());
            return redirect('http://localhost:3000/login?error=redirect_failed');
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $redirectUrl = $this->authService->handleGoogleCallback();
            return redirect()->away($redirectUrl);
        } catch (Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            return redirect()->away('http://localhost:3000/login?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * ========================================================
     * 2. ALUR VERIFIKASI TOKEN (Mobile / Flutter - Google)
     * ========================================================
     */
    public function handleMobileGoogleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $result = $this->authService->handleMobileGoogleLogin($request->id_token);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Login berhasil',
                'access_token' => $result['access_token'],
                'token_type'   => $result['token_type'],
                'user'         => $result['user'],
            ], 200);
        } catch (Exception $e) {
            Log::error('Google Mobile Auth Error: ' . $e->getMessage());
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 401;
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Autentikasi gagal: ' . $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * ========================================================
     * 3. LOGIN MANUAL (Email & Password — Khusus Sistem/Admin)
     * ========================================================
     */
    public function handleManualLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $result = $this->authService->handleManualLogin([
                'email'    => $request->email,
                'password' => $request->password,
            ]);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Login berhasil',
                'access_token' => $result['access_token'],
                'token_type'   => $result['token_type'],
                'user'         => $result['user'],
            ], 200);
        } catch (Exception $e) {
            Log::error('Manual Login Error: ' . $e->getMessage());
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            $message = $statusCode === 500 ? 'Terjadi kesalahan server. Coba beberapa saat lagi.' : $e->getMessage();

            return response()->json([
                'status'  => 'error',
                'message' => $message,
            ], $statusCode);
        }
    }

    /**
     * ========================================================
     * 4. FUNGSI PROTECTED (Wajib bawa Bearer Token)
     * ========================================================
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('personalData');

        return response()->json([
            'status' => 'success',
            'data'   => array_merge(
                $this->authService->formatUserData($user),
                ['personal_data' => $user->personalData]
            )
        ]);
    }

    public function logout(Request $request)
    {
        try {
            Log::info('Logging out user: ' . $request->user()->email);
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil logout'
            ]);
        } catch (Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal logout'
            ], 500);
        }
    }
}