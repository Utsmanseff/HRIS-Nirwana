<?php

namespace App\Notifications;

use App\Models\SanksiDisiplin;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SanksiDitolak extends Notification
{
    use Queueable;

    public function __construct(public SanksiDisiplin $sanksi, public string $alasan) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'sanksi',
            'sanksi_id' => $this->sanksi->id,
            'pesan' => $this->pesan(),
            'url' => '/disiplin',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Usulan Sanksi Ditolak')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/disiplin']);
    }

    private function pesan(): string
    {
        $nama = $this->sanksi->karyawan->nama_lengkap;

        return "Usulan sanksi untuk {$nama} ditolak: {$this->alasan}";
    }
}
