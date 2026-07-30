<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| Public Routes (Tidak membutuhkan token)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Web: Redirect ke Google
    Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('google.login');
    // Web: Callback dari Google
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');

    // Mobile: Verifikasi token Google dari Flutter
    Route::post('/google/mobile', [AuthController::class, 'handleMobileGoogleLogin']);

    // Manual: Login email + password (khusus pengguna sistem/admin)
    Route::post('/login', [AuthController::class, 'handleManualLogin']);
});


/*
|--------------------------------------------------------------------------
| Protected Routes (Wajib mengirim Header "Authorization: Bearer <token>")
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth Actions (Bisa diakses semua user yang sudah login)
    Route::post('/logout', [AuthController::class, 'logout']);

    // User / Profile Management (RESTful)
    // GET /api/user -> Mengambil data profil user beserta relasi personal_data
    Route::get('/user', [AuthController::class, 'user']);

    // PATCH /api/user -> Update profil, dilindungi middleware permission Spatie
    Route::patch('/user', [ProfileController::class, 'updateProfile'])
        ->middleware('permission:manage-profile');

});