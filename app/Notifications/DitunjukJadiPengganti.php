<?php

namespace App\Notifications;

use App\Enums\TipePengganti;
use App\Models\PenugasanPengganti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DitunjukJadiPengganti extends Notification
{
    use Queueable;

    public function __construct(public PenugasanPengganti $rencana) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'pengganti',
            'pengajuan_id' => $this->rencana->pengajuan_cuti_id,
            'pesan' => $this->pesan(),
            'url' => '/absensi/jadwal-saya',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->rencana->tipe->label())
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/absensi/jadwal-saya']);
    }

    private function pesan(): string
    {
        $nama = $this->rencana->karyawanDigantikan?->nama_lengkap ?? 'rekan';
        $mulai = $this->rencana->tanggal_mulai;

        if ($this->rencana->tipe === TipePengganti::Lowongan) {
            return "Anda mengisi jadwal kosong {$nama} mulai ".$mulai->translatedFormat('d M Y').'.';
        }

        $akhir = $this->rencana->tanggal_selesai->translatedFormat('d M Y');

        return "Anda menggantikan dinas {$nama} pada ".$mulai->translatedFormat('d M').' – '.$akhir.'.';
    }
}
