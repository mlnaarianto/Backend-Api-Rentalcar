<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Contract\Messaging;

class SendPushNotification
{
    public function __construct(
        protected Messaging $messaging,
    ) {}

    public function handle(NotificationCreated $event): void
    {
        $notif = $event->notification;

        $user = $notif->user;

        if (!$user || empty($user->fcm_token)) {
            Log::info("SendPushNotification: user_id {$notif->user_id} tidak punya fcm_token, skip push.");
            return;
        }

        try {
            // 🟢 DIPERBAIKI: dulu pakai field 'notification' di sini, yang
            // bikin Android OTOMATIS menampilkan notifikasi sistem sendiri
            // di luar kontrol kode Flutter -- ini yang menyebabkan dobel
            // dengan notifikasi yang sudah ditampilkan lewat WebSocket
            // (channel notifications.{userId}).
            //
            // Sekarang SEMUA dipindah ke 'data' saja (data-only message).
            // Android TIDAK akan auto-tampilkan apa pun untuk tipe pesan
            // ini -- 100% tampilannya dikontrol manual oleh kode Flutter:
            // - Kalau app foreground & WS ready -> WS yang tampilkan
            //   (FCM diabaikan/dedupe, lihat handleFcmMessage() di Flutter)
            // - Kalau app background/killed -> background handler di
            //   Flutter (_firebaseMessagingBackgroundHandler) yang
            //   tampilkan secara manual lewat flutter_local_notifications
            //
            // 🟢 BARU: data-only message secara default dikirim FCM dengan
            // priority NORMAL. Untuk Android, priority normal berarti OS
            // boleh MENUNDA pengiriman pesan kalau device sedang Doze /
            // App Standby -- bisa telat beberapa menit sampai jam, atau
            // bahkan di-drop kalau device jarang dipakai. Ini penyebab
            // utama gejala "kadang notif ga muncul" untuk kondisi app
            // background/killed.
            //
            // Untuk iOS, data-only push butuh dua hal supaya sistem tidak
            // membuang pesannya diam-diam:
            // 1. apns-priority: 10 (pengiriman segera, bukan ditunda)
            // 2. aps.content-available: 1 (menandai ini "background/
            //    silent push" yang sah, sehingga iOS mau membangunkan app
            //    untuk memproses background handler)
            // Tanpa dua ini, iOS sering menahan/membuang silent push,
            // terutama kalau app sudah lama tidak dibuka user.
            $message = CloudMessage::fromArray([
                'token' => $user->fcm_token,
                'data' => [
                    'title'           => (string) ($notif->title ?? 'Informasi Rental'),
                    'message'         => (string) ($notif->message ?? 'Anda memiliki aktivitas baru.'),
                    'notification_id' => (string) $notif->id,
                    'type'            => (string) ($notif->type ?? ''),
                ],
            ])
                ->withAndroidConfig(AndroidConfig::fromArray([
                    // 'high' = FCM diprioritaskan tembus Doze/App Standby.
                    // Tetap data-only (tidak ada key 'notification' di sini),
                    // jadi Android tetap TIDAK auto-tampilkan apa pun --
                    // tampilan tetap 100% dikontrol manual oleh kode Flutter.
                    'priority' => 'high',
                ]))
                ->withApnsConfig(ApnsConfig::fromArray([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                        ],
                    ],
                ]));

            $this->messaging->send($message);

            Log::info("SendPushNotification: push berhasil dikirim ke user_id {$notif->user_id}.");
        } catch (\Throwable $e) {
            Log::error("SendPushNotification: gagal kirim push ke user_id {$notif->user_id}: {$e->getMessage()}");
        }
    }
}