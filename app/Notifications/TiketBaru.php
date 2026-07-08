<?php

namespace App\Notifications;

use App\Models\Tiket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TiketBaru extends Notification
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
            'jenis' => 'tiket_baru',
            'tiket_id' => $this->tiket->id,
            'nomor' => $this->tiket->nomor,
            'pesan' => "Tiket baru {$this->tiket->nomor}: {$this->tiket->judul}",
            'url' => '/tiket/'.$this->tiket->id,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Tiket Baru — Tim '.$this->tiket->tim->label())
            ->body($this->tiket->judul)
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/tiket/'.$this->tiket->id]);
    }
}
