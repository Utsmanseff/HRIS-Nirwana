<?php

namespace App\Notifications;

use App\Enums\TipePengganti;
use App\Models\PenugasanPengganti;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class UsulanPenggantiMasuk extends Notification
{
    use Queueable;

    public function __construct(public PenugasanPengganti $usulan) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'pengganti',
            'pengganti_id' => $this->usulan->id,
            'pesan' => $this->pesan(),
            'url' => '/cuti/pengganti',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Usulan '.$this->usulan->tipe->label())
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/cuti/pengganti']);
    }

    private function pesan(): string
    {
        $pengaju = $this->usulan->karyawan->nama_lengkap;
        $digantikan = $this->usulan->karyawanDigantikan?->nama_lengkap ?? 'rekan';
        $mulai = $this->usulan->tanggal_mulai->translatedFormat('d M Y');

        return $this->usulan->tipe === TipePengganti::Lowongan
            ? "{$pengaju} mengajukan diri mengisi jadwal kosong {$digantikan} mulai {$mulai}."
            : "{$pengaju} mengajukan diri menggantikan {$digantikan} mulai {$mulai}.";
    }
}
