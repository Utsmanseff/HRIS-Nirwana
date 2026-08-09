<?php

namespace App\Notifications;

use App\Models\Jadwal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PengingatAbsenMasuk extends Notification
{
    use Queueable;

    public function __construct(public Jadwal $jadwal) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'absen_masuk',
            'jadwal_id' => $this->jadwal->id,
            'pesan' => $this->pesan(),
            'url' => '/absensi',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Belum absen masuk')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/absensi']);
    }

    private function pesan(): string
    {
        $shift = $this->jadwal->shift;
        $jam = substr((string) $shift?->jam_mulai, 0, 5);

        return "Kamu belum absen masuk untuk shift {$shift?->nama} ({$jam}).";
    }
}
