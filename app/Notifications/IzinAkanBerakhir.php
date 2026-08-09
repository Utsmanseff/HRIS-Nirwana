<?php

namespace App\Notifications;

use App\Enums\SeverityPengingat;
use App\Models\IzinKaryawan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class IzinAkanBerakhir extends Notification
{
    use Queueable;

    public function __construct(
        public IzinKaryawan $izin,
        public SeverityPengingat $severity,
        public int $sisaHari,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'izin',
            'kode_izin' => $this->izin->jenis->kode->value,
            'karyawan_id' => $this->izin->karyawan_id,
            'severity' => $this->severity->value,
            'sisa_hari' => $this->sisaHari,
            'pesan' => $this->pesan(),
            'url' => $this->url($notifiable),
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pengingat Perizinan')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => $this->url($notifiable)]);
    }

    /**
     * Detail karyawan untuk HRD, profil sendiri untuk karyawan ybs — rute /sdm/karyawan
     * ada di grup gate kelola-sdm, jadi menautkannya ke karyawan biasa berujung 403.
     */
    private function url(object $notifiable): string
    {
        return ($notifiable->karyawan_id ?? null) === $this->izin->karyawan_id
            ? '/profil'
            : '/sdm/karyawan/'.$this->izin->karyawan_id;
    }

    private function pesan(): string
    {
        $label = $this->izin->jenis->nama;
        $nama = $this->izin->karyawan->nama_lengkap;

        return $this->severity === SeverityPengingat::Terlewat
            ? "{$label} {$nama} sudah terlewat ".abs($this->sisaHari).' hari.'
            : "{$label} {$nama} berakhir dalam {$this->sisaHari} hari.";
    }
}
