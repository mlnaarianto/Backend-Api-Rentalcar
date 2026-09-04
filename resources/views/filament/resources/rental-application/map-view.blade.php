@php
    $record = $getRecord();
    $lat = (float) ($record?->latitude ?? 1.1301);
    $lng = (float) ($record?->longitude ?? 104.053);
    $address = $record?->formatted_address ?? $record?->business_address ?? 'Tidak ada alamat';
    $mapId = 'filament-admin-map-' . ($record?->getKey() ?? uniqid());
@endphp

<div class="space-y-2" wire:ignore>
    <div class="text-sm font-medium text-gray-700 dark:text-gray-200 flex items-center justify-between">
        <span>Titik Koordinat Lokasi Usaha di Peta</span>
        <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-200">
            Lat: {{ $lat }}, Lng: {{ $lng }}
        </span>
    </div>

    <!-- Container Peta -->
    <div
        id="{{ $mapId }}"
        style="height: 280px; width: 100%; border-radius: 0.75rem;"
        class="border border-gray-300 dark:border-gray-700 z-0"
    ></div>

    <p class="text-xs text-gray-500 italic">
        📌 <strong>Alamat terdeteksi:</strong> {{ $address }}
    </p>
</div>

<!-- Load Leaflet CSS & JS secara dinamis -->
@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
@endonce

<script>
    (function () {
        const mapId = @js($mapId);
        const lat = @js($lat);
        const lng = @js($lng);

        function initMap() {
            const mapElement = document.getElementById(mapId);
            if (!mapElement) return;

            // Kalau Leaflet belum siap, coba lagi sebentar lagi
            if (typeof L === 'undefined') {
                setTimeout(initMap, 100);
                return;
            }

            // Hindari inisialisasi ulang / bentrok instance lama
            if (mapElement._leafletMapInstance) {
                mapElement._leafletMapInstance.remove();
                mapElement._leafletMapInstance = null;
            }
            if (mapElement._leaflet_id) {
                mapElement._leaflet_id = null;
            }

            const map = L.map(mapElement).setView([lat, lng], 15);
            mapElement._leafletMapInstance = map;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup('<b>Lokasi Usaha Perental</b>')
                .openPopup();

            // Perbaiki render peta yang kadang blank karena container
            // belum punya ukuran final saat map di-init (mis. di dalam modal/tab Filament)
            setTimeout(() => map.invalidateSize(), 200);
        }

        // Load pertama kali (full page load)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }

        // Load ulang saat navigasi SPA Livewire (wire:navigate)
        document.addEventListener('livewire:navigated', initMap);

        // Re-init saat komponen Livewire di-refresh (mis. field ini dalam modal/relation manager)
        document.addEventListener('livewire:update', initMap);
    })();
</script>