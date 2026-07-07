<?php

namespace App\Notifications;

use App\Models\SanksiDisiplin;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SanksiDiterbitkan extends Notification
{
    use Queueable;

    public function __construct(public SanksiDisiplin $sanksi) {}

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
            'url' => '/beranda',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Sanksi Diterbitkan')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/beranda']);
    }

    private function pesan(): string
    {
        return "Anda menerima {$this->sanksi->tingkat->label()}. Lihat surat di akun Anda.";
    }
}
