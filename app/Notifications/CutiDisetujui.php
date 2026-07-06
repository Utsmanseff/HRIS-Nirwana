<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class CutiDisetujui extends Notification
{
    use Queueable;

    public function __construct(public PengajuanCuti $pengajuan) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'cuti',
            'pengajuan_id' => $this->pengajuan->id,
            'pesan' => 'Pengajuan cuti Anda telah disetujui.',
            'url' => '/cuti/'.$this->pengajuan->id,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Cuti Disetujui')
            ->body('Pengajuan cuti Anda telah disetujui.')
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/cuti/'.$this->pengajuan->id]);
    }
}
