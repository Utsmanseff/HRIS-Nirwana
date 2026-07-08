<?php

namespace App\Notifications;

use App\Models\Tiket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TiketSelesai extends Notification
{
    use Queueable;

    public function __construct(public Tiket $tiket) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'tiket_selesai',
            'tiket_id' => $this->tiket->id,
            'nomor' => $this->tiket->nomor,
            'pesan' => "Tiket {$this->tiket->nomor} selesai: {$this->tiket->judul}",
            'url' => '/tiket/'.$this->tiket->id,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Tiket Selesai')
            ->body("{$this->tiket->nomor}: {$this->tiket->judul}")
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/tiket/'.$this->tiket->id]);
    }
}
