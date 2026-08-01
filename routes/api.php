<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\NotificationController; // 👈 Ditambahkan agar controller terbaca
use App\Enums\Permission;

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

    // Manual: Login email + password
    Route::post('/login', [AuthController::class, 'handleManualLogin']);
});


/*
|--------------------------------------------------------------------------
| Protected Routes (Wajib mengirim Header "Authorization: Bearer <token>")
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth Actions
    Route::post('/logout', [AuthController::class, 'logout']);

    // User / Profile Management
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user', [ProfileController::class, 'updateProfile'])
        ->middleware('permission:' . Permission::ManageProfile->value);


    /*
    |--------------------------------------------------------------------------
    | Car Management (Kombinasi Middleware Spatie Permission & Policy)
    |--------------------------------------------------------------------------
    */
    Route::prefix('cars')->group(function () {
        
        // GET /api/cars -> Daftar mobil
        Route::get('/', [CarController::class, 'index'])
            ->middleware('permission:' . Permission::ViewCars->value . '|' . Permission::ManageCars->value);

        // GET /api/cars/{id} -> Detail mobil
        Route::get('/{id}', [CarController::class, 'show'])
            ->middleware('permission:' . Permission::ViewCars->value . '|' . Permission::ManageCars->value);

        // POST /api/cars -> Tambah mobil baru
        Route::post('/', [CarController::class, 'store'])
            ->middleware('permission:' . Permission::ManageCars->value); 

        // PUT / PATCH /api/cars/{id} -> Update data mobil
        Route::match(['put', 'patch', 'post'], '/{id}', [CarController::class, 'update'])
            ->middleware('permission:' . Permission::ManageCars->value);

        // DELETE /api/cars/{id} -> Hapus mobil
        Route::delete('/{id}', [CarController::class, 'destroy'])
            ->middleware('permission:' . Permission::ManageCars->value);
            
    });


    // Notification Management
    Route::get('/notifications', [NotificationController::class, 'index']);
    

    /*
    |--------------------------------------------------------------------------
    | Booking Management (Pemesanan, Status, Pembatalan, & Penugasan Driver)
    |--------------------------------------------------------------------------
    */

    // GET /api/drivers-list -> Ambil daftar driver yang tersedia (Bisa diakses Perental/Admin)
    Route::get('/drivers-list', [BookingController::class, 'getAvailableDrivers'])
        ->middleware('permission:' . Permission::ManageBookings->value);

    Route::prefix('bookings')->group(function () {

        // GET /api/bookings -> Lihat daftar booking
        Route::get('/', [BookingController::class, 'index'])
            ->middleware('permission:' . Permission::ManageBookings->value . '|' . Permission::ViewOwnBooking->value . '|' . Permission::ViewAssignedBooking->value);

        // GET /api/bookings/{id} -> Lihat detail booking spesifik
        Route::get('/{id}', [BookingController::class, 'show'])
            ->middleware('permission:' . Permission::ManageBookings->value . '|' . Permission::ViewOwnBooking->value . '|' . Permission::ViewAssignedBooking->value);

        // POST /api/bookings -> Buat pesanan baru (Khusus Penyewa)
        Route::post('/', [BookingController::class, 'store'])
            ->middleware('permission:' . Permission::CreateBooking->value);

        // PATCH /api/bookings/{id}/cancel -> Batalkan booking (Khusus Penyewa)
        Route::patch('/{id}/cancel', [BookingController::class, 'cancel'])
            ->middleware('permission:' . Permission::CancelOwnBooking->value);

        // PATCH /api/bookings/{id}/status -> Perbarui status booking (Khusus Perental / Driver / Admin)
        Route::patch('/{id}/status', [BookingController::class, 'updateStatus'])
            ->middleware('permission:' . Permission::UpdateBookingStatus->value . '|' . Permission::ManageBookings->value);

        // PATCH /api/bookings/{id}/assign-driver -> Tugaskan Driver ke Pesanan (Khusus Perental / Admin)
        Route::patch('/{id}/assign-driver', [BookingController::class, 'assignDriver'])
            ->middleware('permission:' . Permission::ManageBookings->value);

    });

});