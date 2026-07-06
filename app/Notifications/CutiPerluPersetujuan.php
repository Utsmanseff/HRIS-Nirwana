<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class CutiPerluPersetujuan extends Notification
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
            'pesan' => $this->pesan(),
            'url' => '/cuti/persetujuan',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Persetujuan Cuti')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/cuti/persetujuan']);
    }

    private function pesan(): string
    {
        $nama = $this->pengajuan->karyawan->nama_lengkap;

        return "Pengajuan cuti {$nama} menunggu persetujuan Anda.";
    }
}
