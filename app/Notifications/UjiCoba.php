<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Notifikasi percobaan untuk memverifikasi web push di HP asli.
 * Tak punya ikon khusus di halaman notifikasi; jatuh ke fallback 'bell' — disengaja.
 */
class UjiCoba extends Notification
{
    use Queueable;

    private const PESAN = 'Notifikasi percobaan — bila ini muncul di HP, web push sudah aktif.';

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'uji',
            'pesan' => self::PESAN,
            'url' => '/notifikasi',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Uji Notifikasi NirwanaHRIS')
            ->body(self::PESAN)
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/notifikasi']);
    }
}
