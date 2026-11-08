<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

return [
    /*
     * Prefix route yang didokumentasikan.
     */
    'api_path' => 'api',

    'api_domain' => null,

    /*
     * Lokasi default hasil `php artisan scramble:export`.
     * Biar langsung dibaca Swagger UI (/docs/api.json), arahkan ke public/docs.
     */
    'export_path' => 'public/docs/api.json',

    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],

    /*
     * Pilih route yang ditampilkan:
     * - hanya yang berawalan api/
     * - endpoint testing (simulate-qris-pay) disembunyikan
     * AuthController TIDAK lagi dikecualikan.
     */
    'routes' => function (\Illuminate\Routing\Route $route) {
        $uri = $route->uri();

        return str_starts_with($uri, 'api/')
            && ! str_contains($uri, 'testing/');
    },



    // Ganti blok 'info' di config/scramble.php dengan ini.
    'info' => [
        'version' => env('API_VERSION', '1.0.0'),

        'description' => <<<'MD'
API untuk aplikasi **Car Rental** (Flutter & React).

---

### Login Mobile (Flutter)
1. Aplikasi menampilkan Google Sign-In dan mendapat `id_token`.
2. Kirim ke `POST /auth/google/mobile` dengan body `{ "id_token": "..." }`.
3. Ambil `access_token` dari response.
4. Klik **Authorize** (gembok kanan atas), tempel token **tanpa** kata `Bearer`.

### Login Web (React)
Bukan endpoint JSON, jadi tidak bisa dicoba dari sini. Arahkan browser ke:
`http://localhost:8000/auth/google`
Setelah login Google, server me-redirect ke frontend membawa token.

### Refresh token
`POST /auth/refresh` memakai **refresh token**, bukan access token.
Ganti isi **Authorize** dengan refresh token sebelum menjalankannya.

---

### Cara tes di Swagger

`id_token` hanya bisa dibuat oleh Google, jadi ada dua cara untuk mendapat token.

#### Cara 1: Google OAuth Playground (menguji login Google yang asli)
1. Buka https://developers.google.com/oauthplayground
2. Klik ikon gerigi, centang **Use your own OAuth credentials**, lalu isi Client ID dan Client Secret dari client **Laravel Backend** (tipe Web application).
3. Di Google Cloud Console, tambahkan `https://developers.google.com/oauthplayground` ke **Authorized redirect URIs** pada client yang sama.
4. Di kotak **Input your own scopes** isi `openid email profile`, klik **Authorize APIs**, lalu pilih akun Google.
5. Klik **Exchange authorization code for tokens**, lalu salin nilai `id_token` (diawali `eyJ`).
6. Tempel ke body `POST /auth/google/mobile` di sini dan klik **Execute**.
7. Salin `access_token` dari response, lalu tempel di **Authorize**.

`id_token` hanya berlaku sekitar 1 jam. Kalau server menolak, ulangi langkah 4 sampai 6.

#### Cara 2: Token dari Tinker (paling cepat, tanpa Google)
Hanya untuk development. User harus sudah ada di database.

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'email@contoh.com')->first();
$user->createToken('access|test', ['access-api'])->plainTextToken;
```

Tempel hasilnya di **Authorize**. Untuk mencoba `/auth/refresh`, buat refresh token:

```php
$user->createToken('refresh|test', ['issue-access-token'])->plainTextToken;
```

---

### Catatan
- Permission tiap endpoint mengikuti role user. Kalau muncul **403**, role user belum punya permission yang dibutuhkan.
- Kalau muncul **401**, token salah, kedaluwarsa, atau memakai jenis token yang keliru (access vs refresh).
- Kalau muncul **419** pada request POST, cek `SANCTUM_STATEFUL_DOMAINS` di `.env`. Host halaman dokumentasi ini tidak boleh ada di daftar itu.
MD,
    ],

    'ui' => [
        'title' => 'Car Rental API Documentation',
    ],

    'renderer' => 'elements',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],

    /*
     * null = otomatis dari APP_URL + api_path (http://host:8000/api).
     */
    'servers' => null,

    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    /*
     * Gembok otomatis berdasarkan middleware route:
     * - route ber-middleware auth / auth:* (mis. auth:sanctum) => bearer auth
     * - route tanpa auth (login Google) => ditandai publik
     */
    'security_strategy' => [
        MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['auth', 'auth:*'],
            'scheme' => SecurityScheme::http('bearer'),
        ],
    ],
];
