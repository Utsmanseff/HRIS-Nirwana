<?php

namespace App\Notifications;

use App\Models\Absensi;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PengingatAbsenPulang extends Notification
{
    use Queueable;

    public function __construct(public Absensi $absensi) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'absen_pulang',
            'absensi_id' => $this->absensi->id,
            'pesan' => $this->pesan(),
            'url' => '/absensi',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Belum absen pulang')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/absensi']);
    }

    private function pesan(): string
    {
        $masuk = $this->absensi->jam_masuk->translatedFormat('j M H:i');

        return "Sesi absen sejak {$masuk} masih terbuka. Jangan lupa absen pulang.";
    }
}
