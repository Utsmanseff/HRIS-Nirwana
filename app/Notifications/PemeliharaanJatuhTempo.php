<?php

namespace App\Notifications;

use App\Enums\SeverityPengingat;
use App\Models\JadwalPemeliharaan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PemeliharaanJatuhTempo extends Notification
{
    use Queueable;

    public function __construct(
        public JadwalPemeliharaan $jadwal,
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
            'jenis' => 'pemeliharaan',
            'jadwal_id' => $this->jadwal->id,
            'aset_id' => $this->jadwal->aset_id,
            'severity' => $this->severity->value,
            'sisa_hari' => $this->sisaHari,
            'pesan' => $this->pesan(),
            'url' => '/inventaris/'.$this->jadwal->aset_id,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pengingat Pemeliharaan')
            ->body($this->pesan())
            ->icon('/img/android-chrome-192x192.png')
            ->data(['url' => '/inventaris/'.$this->jadwal->aset_id]);
    }

    private function pesan(): string
    {
        $aset = $this->jadwal->aset->nama;
        $jadwal = $this->jadwal->nama;

        return $this->severity === SeverityPengingat::Terlewat
            ? "Pemeliharaan {$jadwal} untuk {$aset} sudah terlewat ".abs($this->sisaHari).' hari.'
            : "Pemeliharaan {$jadwal} untuk {$aset} jatuh tempo dalam {$this->sisaHari} hari.";
    }
}
