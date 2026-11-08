<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Rental API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <style>
        /* Menyesuaikan sedikit styling agar lebih rapi dan modern */
        body {
            margin: 0;
            background: #fafafa;
        }
        .swagger-ui .topbar {
            display: none; /* Menyembunyikan topbar bawaan swagger jika tidak perlu */
        }

        /* Sembunyikan teks ringkasan di samping path (tampilkan path saja) */
        .swagger-ui .opblock-summary-description {
            display: none;
        }

        .swagger-ui .opblock-summary-description {
    display: none;
}
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: '/docs/api.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                persistAuthorization: true, // token tidak hilang saat refresh halaman
                docExpansion: 'none',       // semua endpoint tertutup di awal
                filter: true,               // kolom pencarian endpoint
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
            });
        };
    </script>
</body>
</html>