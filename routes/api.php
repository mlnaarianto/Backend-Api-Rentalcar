<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RentalApplicationController;
// use App\Http\Controllers\Api\AdminRentalApplicationController; // ✅ tambahkan import di paling atas file
use App\Http\Controllers\Api\ChatController;
use App\Enums\Permission;

/*
|--------------------------------------------------------------------------
| Public Routes (Tidak membutuhkan token)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Web: Redirect & Callback Google OAuth dipindah ke routes/web.php
    // (sudah ditangani di sana, jangan didaftarkan lagi di sini supaya
    // tidak ada dua route yang bentrok untuk path yang sama).

    // Mobile: Verifikasi token Google dari Flutter
    Route::post('/google/mobile', [AuthController::class, 'handleMobileGoogleLogin']);

    // Manual: Login email + password
    // NONAKTIF: login manual khusus Admin/sistem sudah digantikan oleh
    // panel Filament (punya auth sendiri). Method controller-nya juga
    // sudah dikomentari di AuthController & AuthService — jangan
    // diaktifkan lagi tanpa membuka comment di kedua file itu juga.
    // Route::post('/login', [AuthController::class, 'handleManualLogin']);
});


/*
|--------------------------------------------------------------------------
| 🚧 TESTING ONLY - Simulasi Pembayaran QRIS Sandbox Midtrans
|--------------------------------------------------------------------------
| Tetap dipertahankan tanpa auth:sanctum supaya gampang dites lewat browser
| (masih development). TAPI sekarang dikunci dengan abort_unless(local) —
| jadi kalau APP_ENV di server production BUKAN 'local', route ini otomatis
| balas 404, apapun yang terjadi, walaupun kelupaan dihapus dari codebase.
|
| 👉 INGAT: sebelum deploy pertama ke production, cek dulu APP_ENV di server
| production benar-benar 'production', bukan 'local'. Kalau APP_ENV sampai
| ketinggalan 'local' di server production, guard ini nggak akan menolong.
| Paling aman tetap: hapus blok ini begitu fase development selesai.
|--------------------------------------------------------------------------
*/
Route::get('/testing/simulate-qris-pay/{orderId}', function ($orderId) {
    abort_unless(app()->environment('local'), 404);

    $response = \Illuminate\Support\Facades\Http::withBasicAuth(
        config('services.midtrans.server_key'),
        ''
    )->post("https://api.sandbox.midtrans.com/v2/qris/{$orderId}/pay");

    return response()->json([
        // 👇 server_key_used DIHAPUS dari response — nggak perlu diekspos
        // walau cuma buat "debug", karena response ini kekirim balik ke
        // browser/klien yang manggil, bukan cuma kelihatan di server log.
        'status_code' => $response->status(),
        'headers'     => $response->headers(),
        'raw_body'    => $response->body(),
        'parsed_json' => $response->json(),
    ]);
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
    | Rental Application / Verifikasi Perental
    |--------------------------------------------------------------------------
    */
    Route::prefix('rental-application')->group(function () {
        // GET /api/rental-application -> Cek status pengajuan user yang sedang login
        Route::get('/', [RentalApplicationController::class, 'show'])
            ->middleware('permission:' . Permission::ManageProfile->value);

        // GET /api/rental-application/reverse-geocode -> Auto-fill alamat dari klik peta
        Route::get('/reverse-geocode', [RentalApplicationController::class, 'reverseGeocode'])
            ->middleware('permission:' . Permission::ManageProfile->value);

        // POST /api/rental-application -> Kirim atau update data pengajuan & dokumen persyaratan
        Route::post('/', [RentalApplicationController::class, 'storeOrUpdate'])
            ->middleware('permission:' . Permission::ManageProfile->value);
    });


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
    Route::post('/notifications/read-all', [NotificationController::class, 'markAsRead']);
    // 👇 TAMBAHKAN DUA ROUTE INI
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'clearAll']);

    // Push Notification (FCM) — simpan/perbarui token device untuk user yang login
    Route::post('/fcm-token', [NotificationController::class, 'updateFcmToken']);

    // Chat Management (Kirim pesan, Reverb, & FCM)
    Route::post('/messages', [ChatController::class, 'sendMessage']);


    // GET /api/chats/{chatId}/messages -> Ambil riwayat pesan dalam 1 room chat
    Route::get('/chats/{chatId}/messages', [ChatController::class, 'getMessages']);

    // GET /api/chats/{chatId}/resolve -> Resolve chatId (id atau room_identifier) ke id numeric asli
    Route::get('/chats/{chatId}/resolve', [ChatController::class, 'resolveChatId']);

    // DELETE /api/messages/{messageId} -> Hapus pesan secara permanen (hard delete)
    Route::delete('/messages/{messageId}', [ChatController::class, 'deleteMessage']);


    /*
    |--------------------------------------------------------------------------
    | Booking Management (Pemesanan, Status, Pembatalan, & Penugasan Driver)
    |--------------------------------------------------------------------------
    */

    // GET /api/drivers-list -> Ambil daftar driver yang tersedia
    // (👈 ditambah ManageAllBookings supaya Super Admin tidak ke-block middleware)
    Route::get('/drivers-list', [BookingController::class, 'getAvailableDrivers'])
        ->middleware('permission:'
            . Permission::ManageAllBookings->value . '|'
            . Permission::ManageBookings->value);

    Route::prefix('bookings')->group(function () {

        // GET /api/bookings/my-history -> Riwayat pemesanan MILIK SENDIRI sebagai
        // penyewa. Selalu scoped ke user_id user yang login, apapun role atau
        // permission yang dia punya (Perental/Driver/Admin sekalipun). Tidak
        // butuh middleware permission tambahan karena hasilnya selalu personal
        // (auth:sanctum dari group luar sudah cukup). Sengaja ditaruh SEBELUM
        // '/{id}' supaya 'my-history' tidak ketangkap sebagai parameter {id}.
        Route::get('/my-history', [BookingController::class, 'myHistory']);

        // GET /api/bookings -> Lihat daftar booking (scoping detail ada di BookingService)
        Route::get('/', [BookingController::class, 'index'])
            ->middleware('permission:'
                . Permission::ManageAllBookings->value . '|'
                . Permission::ManageBookings->value . '|'
                . Permission::ViewOwnBooking->value . '|'
                . Permission::ViewAssignedBooking->value);

        // GET /api/bookings/{id} -> Lihat detail booking spesifik
        Route::get('/{id}', [BookingController::class, 'show'])
            ->middleware('permission:'
                . Permission::ManageAllBookings->value . '|'
                . Permission::ManageBookings->value . '|'
                . Permission::ViewOwnBooking->value . '|'
                . Permission::ViewAssignedBooking->value);

        // GET /api/bookings/{id}/check-payment -> Cek status pembayaran QRIS ke Midtrans
        // (Cuma baca status dari Midtrans, tidak menulis manual — aman untuk Penyewa juga)
        Route::get('/{id}/check-payment', [BookingController::class, 'checkPayment'])
            ->middleware('permission:'
                . Permission::ManageAllBookings->value . '|'
                . Permission::ManageBookings->value . '|'
                . Permission::ViewOwnBooking->value . '|'
                . Permission::ViewAssignedBooking->value);

        // POST /api/bookings -> Buat pesanan baru (Khusus Penyewa)
        Route::post('/', [BookingController::class, 'store'])
            ->middleware('permission:' . Permission::CreateBooking->value);

        // PATCH /api/bookings/{id}/cancel -> Batalkan booking (Khusus Penyewa, miliknya sendiri)
        Route::patch('/{id}/cancel', [BookingController::class, 'cancel'])
            ->middleware('permission:' . Permission::CancelOwnBooking->value);

        // PATCH /api/bookings/{id}/status -> Perbarui status booking
        // (Khusus Perental / Driver / Admin. Penyewa TIDAK punya permission ini
        // lagi — lihat App\Enums\Role::Penyewa->permissions())
        Route::patch('/{id}/status', [BookingController::class, 'updateStatus'])
            ->middleware('permission:'
                . Permission::ManageAllBookings->value . '|'
                . Permission::UpdateBookingStatus->value . '|'
                . Permission::ManageBookings->value);

        // PATCH /api/bookings/{id}/assign-driver -> Tugaskan Driver (Khusus Perental / Admin)
        Route::patch('/{id}/assign-driver', [BookingController::class, 'assignDriver'])
            ->middleware('permission:'
                . Permission::ManageAllBookings->value . '|'
                . Permission::ManageBookings->value);
    });

    /*
    |--------------------------------------------------------------------------
    | [ADMIN] Rental Application / Verifikasi Perental
    |--------------------------------------------------------------------------
    | NONAKTIF: dulu disiapkan sebagai cadangan kalau ada admin panel React
    | terpisah dari Filament. Sekarang approval perental sudah sepenuhnya
    | ditangani lewat Filament, jadi seluruh blok endpoint ini dikomentari
    | total (bukan dihapus) supaya gampang diaktifkan lagi kalau suatu saat
    | dibutuhkan.
    |--------------------------------------------------------------------------
    */
    // Route::prefix('admin/rental-applications')->group(function () {
    //     Route::get('/', [AdminRentalApplicationController::class, 'index'])
    //         ->middleware('permission:' . Permission::VerifyPerental->value);

    //     Route::get('/{id}', [AdminRentalApplicationController::class, 'show'])
    //         ->middleware('permission:' . Permission::VerifyPerental->value);

    //     Route::patch('/{id}/verify', [AdminRentalApplicationController::class, 'verify'])
    //         ->middleware('permission:' . Permission::VerifyPerental->value);
    // });
});
