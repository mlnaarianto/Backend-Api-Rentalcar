<div class="space-y-4 p-4 bg-white rounded-xl shadow border">
    <h3 class="text-lg font-bold text-gray-800">Dokumen Verifikasi Pengguna (KTP & SIM)</h3>
    
    @php
        // Karena record di sini adalah model PersonalData itu sendiri
        $personalData = $record ?? null;
    @endphp

    @if($personalData)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Foto KTP -->
            <div class="border p-3 rounded-lg bg-gray-50">
                <p class="font-semibold text-sm text-gray-700 mb-2">Foto KTP:</p>
                @if($personalData->ktp_image ?? false)
                    @php
                        $ktpPath = $personalData->ktp_image;
                        $ktpUrl = filter_var($ktpPath, FILTER_VALIDATE_URL) ? $ktpPath : asset('storage/' . $ktpPath);
                    @endphp
                    <a href="{{ $ktpUrl }}" target="_blank">
                        <img src="{{ $ktpUrl }}" alt="KTP" class="w-full h-48 object-cover rounded-md border hover:opacity-90 transition">
                    </a>
                    <p class="text-xs text-gray-500 mt-1">Klik gambar untuk memperbesar</p>
                @else
                    <div class="h-48 flex items-center justify-center bg-gray-100 rounded-md border text-gray-400 text-sm">
                        KTP belum diunggah.
                    </div>
                @endif
            </div>

            <!-- Foto SIM -->
            <div class="border p-3 rounded-lg bg-gray-50">
                <p class="font-semibold text-sm text-gray-700 mb-2">
                    Foto SIM (No: {{ $personalData->sim_number ?? '-' }}):
                </p>
                @if($personalData->sim_image ?? false)
                    @php
                        $simPath = $personalData->sim_image;
                        $simUrl = filter_var($simPath, FILTER_VALIDATE_URL) ? $simPath : asset('storage/' . $simPath);
                    @endphp
                    <a href="{{ $simUrl }}" target="_blank">
                        <img src="{{ $simUrl }}" alt="SIM" class="w-full h-48 object-cover rounded-md border hover:opacity-90 transition">
                    </a>
                    <p class="text-xs text-gray-500 mt-1">Klik gambar untuk memperbesar</p>
                @else
                    <div class="h-48 flex items-center justify-center bg-gray-100 rounded-md border text-gray-400 text-sm">
                        SIM belum diunggah.
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm">
            Data personal pengguna tidak ditemukan.
        </div>
    @endif
</div>