@php
    $url = $getRecord()->video_url;
    $youtubeMatch = null;

    if ($url) {
        preg_match(
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
            $url,
            $youtubeMatch
        );
    }
@endphp

@if($url)
    <div class="not-prose w-full" style="width: 100%;">
        @if(isset($youtubeMatch[1]))
            {{-- Video YouTube --}}
            <div class="not-prose aspect-video w-full rounded-xl overflow-hidden bg-black shadow-md" style="width: 100%; aspect-ratio: 16 / 9;">
                <iframe
                    src="https://www.youtube.com/embed/{{ $youtubeMatch[1] }}?autoplay=1"
                    title="YouTube video player"
                    class="w-full h-full border-0"
                    style="width: 100%; height: 100%; border: 0;"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        @else
            @php
                $videoSource = $url;

                // Konversi link Google Drive share ke link direct-view
                if (preg_match('/drive\.google\.com\/file\/d\/([^\/]+)/', $url, $driveMatch)) {
                    $videoSource = "https://drive.google.com/uc?export=download&id={$driveMatch[1]}";
                }
            @endphp

            {{-- Video file langsung / Google Drive --}}
            <div class="not-prose aspect-video w-full rounded-xl overflow-hidden bg-black shadow-md" style="width: 100%; aspect-ratio: 16 / 9;">
                <video
                    src="{{ $videoSource }}"
                    controls
                    autoplay
                    class="w-full h-full object-contain"
                    style="width: 100%; height: 100%;"
                >
                    Browser Anda tidak mendukung pemutar video ini.
                </video>
            </div>
        @endif
    </div>
@endif