<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class CutiDitolak extends Notification
{
    use Queueable;

    public function __construct(public PengajuanCuti $pengajuan, public ?string $catatan = null) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'cuti',
            'pengajuan_id' => $this->pengajuan->id,
            'pesan' => $this->pesan(),
            'url' => '/cuti/'.$this->pengajuan->id,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Cuti Ditolak')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/cuti/'.$this->pengajuan->id]);
    }

    private function pesan(): string
    {
        $dasar = 'Pengajuan cuti Anda ditolak.';

        return $this->catatan ? $dasar.' Catatan: '.$this->catatan : $dasar;
    }
}
