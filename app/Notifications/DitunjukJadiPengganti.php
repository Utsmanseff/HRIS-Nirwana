<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DitunjukJadiPengganti extends Notification
{
    use Queueable;

    public function __construct(public PengajuanCuti $pengajuan, public PenugasanPengganti $rencana) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'pengganti-cuti',
            'pengajuan_id' => $this->pengajuan->id,
            'pesan' => $this->pesan(),
            'url' => '/absensi/jadwal-saya',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pengganti Cuti')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/absensi/jadwal-saya']);
    }

    private function pesan(): string
    {
        $nama = $this->pengajuan->karyawan->nama_lengkap;
        $mulai = $this->rencana->tanggal_mulai->translatedFormat('d M');
        $akhir = $this->rencana->tanggal_selesai->translatedFormat('d M Y');

        return "Anda menggantikan dinas {$nama} pada ".$mulai.' – '.$akhir.'.';
    }
}
