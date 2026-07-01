<?php

namespace App\Notifications;

use App\Enums\SeverityPengingat;
use App\Models\Karyawan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SipAkanBerakhir extends Notification
{
    use Queueable;

    public function __construct(
        public Karyawan $karyawan,
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
            'jenis' => 'sip',
            'karyawan_id' => $this->karyawan->id,
            'severity' => $this->severity->value,
            'sisa_hari' => $this->sisaHari,
            'pesan' => $this->pesan(),
            'url' => '/sdm/karyawan/'.$this->karyawan->id,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pengingat SIP')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/sdm/karyawan/'.$this->karyawan->id]);
    }

    private function pesan(): string
    {
        $nama = $this->karyawan->nama_lengkap;

        return $this->severity === SeverityPengingat::Terlewat
            ? "SIP {$nama} sudah terlewat ".abs($this->sisaHari).' hari.'
            : "SIP {$nama} berakhir dalam {$this->sisaHari} hari.";
    }
}
