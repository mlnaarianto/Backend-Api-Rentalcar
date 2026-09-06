<x-filament-panels::page>
    <style>
        .chat-shell {
            display: flex;
            gap: 20px;
            height: 75vh;
            min-height: 550px;
            width: 100%;
        }
        .chat-panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        html.dark .chat-panel { background: #111827; border-color: #374151; }

        .chat-header { padding: 15px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
        html.dark .chat-header { background: #1f2937; border-color: #374151; }
        .chat-header h3 { font-weight: bold; font-size: 15px; color: #1f2937; margin: 0; }
        html.dark .chat-header h3 { color: #f3f4f6; }
        .chat-header span { font-size: 13px; font-weight: 600; color: #4b5563; }
        html.dark .chat-header span { color: #d1d5db; }
        .chat-header .room-title { color: #d97706; font-weight: bold; }
        html.dark .chat-header .room-title { color: #fbbf24; }

        #admin-chat-list { flex: 1; overflow-y: auto; }
        .chat-list-empty { text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 14px; }
        html.dark .chat-list-empty { color: #6b7280; }

        .chat-list-item { padding: 15px; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.2s; }
        html.dark .chat-list-item { border-color: #1f2937; }
        .chat-list-item:hover { background: #f9fafb; }
        html.dark .chat-list-item:hover { background: #1f2937; }
        .chat-list-item.selected { background: #fef3c7; border-left: 4px solid #d97706; }
        html.dark .chat-list-item.selected { background: rgba(217, 119, 6, 0.15); border-left: 4px solid #fbbf24; }
        .chat-list-item .name { font-weight: bold; font-size: 14px; color: #111827; margin-bottom: 4px; }
        html.dark .chat-list-item .name { color: #f3f4f6; }
        .chat-list-item .last-msg { font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        html.dark .chat-list-item .last-msg { color: #9ca3af; }

        #admin-chat-box {
            flex: 1; padding: 20px; overflow-y: auto; background: #f9fafb;
            display: flex; flex-direction: column; gap: 12px;
        }
        html.dark #admin-chat-box { background: #1f2937; }
        .chat-box-empty {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; height: 100%; color: #9ca3af; font-size: 14px;
        }
        html.dark .chat-box-empty { color: #6b7280; }

        .chat-input-bar { padding: 12px 15px; border-top: 1px solid #e5e7eb; background: #ffffff; }
        html.dark .chat-input-bar { background: #111827; border-color: #374151; }
        .chat-input-bar .row { display: flex; gap: 10px; }

        #admin-message-input {
            flex: 1; padding: 10px 14px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 13px; outline: none;
            background: #ffffff; color: #111827;
        }
        html.dark #admin-message-input { background: #1f2937; border-color: #4b5563; color: #f3f4f6; }
        #admin-message-input::placeholder { color: #9ca3af; }
        html.dark #admin-message-input::placeholder { color: #6b7280; }

        #send-btn {
            padding: 10px 20px; background: #d97706; color: white;
            font-weight: 600; border: none; border-radius: 8px;
            font-size: 13px; cursor: pointer;
        }
        #send-btn:hover { background: #b45309; }

        .msg-bubble {
            max-width: 70%; padding: 10px 14px; border-radius: 12px;
            font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            position: relative;
        }
        .msg-bubble.from-admin { background: #d97706; color: #ffffff; border: none; }
        .msg-bubble.from-customer { background: #ffffff; color: #1f2937; border: 1px solid #e5e7eb; }
        html.dark .msg-bubble.from-customer { background: #374151; color: #f3f4f6; border-color: #4b5563; }
        .msg-bubble .sender { font-size: 10px; font-weight: 600; margin-bottom: 2px; opacity: 0.8; }
        .msg-bubble p { margin: 0; line-height: 1.4; word-break: break-word; }
        .msg-bubble .time { font-size: 9px; display: block; text-align: right; margin-top: 4px; opacity: 0.7; }

        /* 🟢 BARU: tombol hapus, hanya tampil di bubble milik admin sendiri */
        .msg-bubble .delete-btn {
            margin-top: 4px;
            font-size: 9px;
            opacity: 0.75;
            background: none;
            border: none;
            padding: 0;
            color: inherit;
            cursor: pointer;
            text-decoration: underline;
            display: block;
            margin-left: auto;
        }
        .msg-bubble .delete-btn:hover { opacity: 1; }
    </style>

    {{-- Catatan: TIDAK ada wire:poll di sini sama sekali.
         Refresh dipicu murni oleh event WebSocket dari Reverb (lihat script
         di bawah), yang memanggil Livewire.dispatch('refresh-chat-list'). --}}
    <div class="chat-shell">

        {{-- KOLOM KIRI: Daftar Inbox Customer --}}
        <div class="chat-panel" style="width: 35%;">
            <div class="chat-header">
                <h3>Inbox Customer</h3>
            </div>

            <div id="admin-chat-list">
                @forelse ($this->chats as $chat)
                    <div
                        wire:click="selectChat({{ $chat['id'] }})"
                        wire:key="chat-item-{{ $chat['id'] }}"
                        class="chat-list-item {{ $activeChatId === $chat['id'] ? 'selected' : '' }}"
                    >
                        <div class="name">{{ $chat['display_name'] }}</div>
                        <div class="last-msg">{{ $chat['last_message'] ?? '' }}</div>
                    </div>
                @empty
                    <div class="chat-list-empty">Belum ada pesan masuk.</div>
                @endforelse
            </div>
        </div>

        {{-- KOLOM KANAN: Ruang Obrolan --}}
        <div class="chat-panel" style="width: 65%;">
            <div class="chat-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Room: <span class="room-title">{{ $this->activeChatTitle }}</span></span>
            </div>

            <div id="admin-chat-box" x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
                @forelse ($this->messages as $message)
                    @php $isAdmin = $message->sender_id === auth()->id(); @endphp
                    <div style="display:flex; justify-content: {{ $isAdmin ? 'flex-end' : 'flex-start' }}; width:100%;">
                        <div class="msg-bubble {{ $isAdmin ? 'from-admin' : 'from-customer' }}" wire:key="msg-{{ $message->id }}">
                            <div class="sender">{{ $message->sender->name ?? '-' }}</div>
                            <p>{{ $message->text }}</p>
                            <span class="time">{{ $message->created_at->format('H:i') }}</span>
                            {{-- 🟢 BARU: tombol hapus, hanya untuk pesan milik admin sendiri --}}
                            @if($isAdmin)
                                <button
                                    type="button"
                                    class="delete-btn"
                                    wire:click="deleteMessage({{ $message->id }})"
                                    onclick="return confirm('Hapus pesan ini secara permanen?')"
                                >Hapus</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="chat-box-empty">
                        <p>{{ $activeChatId ? 'Belum ada riwayat pesan di room ini.' : 'Pilih salah satu customer di sebelah kiri untuk mulai membalas.' }}</p>
                    </div>
                @endforelse
            </div>

            <div class="chat-input-bar">
                <div class="row">
                    <input
                        id="admin-message-input"
                        type="text"
                        placeholder="Ketik balasan untuk customer..."
                        wire:model="newMessageText"
                        wire:keydown.enter="sendMessage"
                    />
                    <button id="send-btn" type="button" wire:click="sendMessage">Kirim</button>
                </div>
            </div>
        </div>

    </div>

    {{-- 🟢 LARAVEL ECHO + REVERB REALTIME LISTENER (pengganti Firebase JS SDK lama) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script>
        (function () {
            // Hindari re-inisialisasi Echo kalau Livewire re-render bagian lain
            // dari halaman ini (script tag ini letaknya di luar area yang
            // di-morph Livewire, tapi guard ini jaga-jaga tambahan).
            if (window.__adminChatEchoInitialized) {
                return;
            }
            window.__adminChatEchoInitialized = true;

            window.Pusher = Pusher;

            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: '{{ env('REVERB_APP_KEY') }}',
                wsHost: '{{ env('REVERB_HOST') }}',
                wsPort: {{ env('REVERB_PORT', 9090) }},
                wssPort: {{ env('REVERB_PORT', 9090) }},
                forceTLS: {{ env('REVERB_SCHEME', 'http') === 'https' ? 'true' : 'false' }},
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                },
            });

            // Channel bersama: dengar SEMUA pesan baru & pesan dihapus dari
            // mana pun asalnya, lalu minta Livewire refresh (recompute
            // daftar chat + pesan aktif).
            window.Echo.private('admin-chat-list')
                .listen('.message.sent', () => {
                    Livewire.dispatch('refresh-chat-list');
                })
                .listen('.message.deleted', () => {
                    Livewire.dispatch('refresh-chat-list');
                })
                .error((error) => {
                    console.error('Reverb admin-chat-list error:', error);
                });
        })();
    </script>
</x-filament-panels::page>