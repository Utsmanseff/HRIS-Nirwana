<?php

namespace App\Notifications;

use App\Models\PenggantiCuti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class UsulanPenggantiMasuk extends Notification
{
    use Queueable;

    public function __construct(public PenggantiCuti $usulan) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'pengganti-cuti',
            'pengganti_id' => $this->usulan->id,
            'pesan' => $this->pesan(),
            'url' => '/cuti/pengganti',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Usulan Pengganti Cuti')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/cuti/pengganti']);
    }

    private function pesan(): string
    {
        $pengaju = $this->usulan->karyawan->nama_lengkap;
        $pemohon = $this->usulan->pengajuan->karyawan->nama_lengkap;
        $mulai = $this->usulan->tanggal_mulai->translatedFormat('d M Y');

        return "{$pengaju} mengajukan diri menggantikan {$pemohon} mulai {$mulai}.";
    }
}
