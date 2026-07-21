<?php

namespace App\Console\Commands;

use App\Models\Karyawan;
use App\Notifications\UjiCoba;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notif:uji {nip : NIP karyawan tujuan}')]
#[Description('Kirim notifikasi percobaan (in-app + web push) ke satu karyawan untuk uji device')]
class KirimNotifUji extends Command
{
    public function handle(): int
    {
        $nip = (string) $this->argument('nip');

        $karyawan = Karyawan::where('nip', $nip)->first();
        if (! $karyawan) {
            $this->error("Karyawan dengan NIP {$nip} tidak ada.");

            return self::FAILURE;
        }

        $user = $karyawan->user()->first();
        if (! $user) {
            $this->error("Karyawan {$nip} belum tertaut akun pengguna.");

            return self::FAILURE;
        }

        $jumlahLangganan = $user->pushSubscriptions()->count();
        $user->notify(new UjiCoba);

        $this->info("Notifikasi uji dikirim ke {$user->namaTampilan()} ({$nip}).");
        $this->line("Langganan web push aktif: {$jumlahLangganan}");

        if ($jumlahLangganan === 0) {
            $this->warn('Belum ada langganan push — notifikasi hanya masuk in-app. Aktifkan notifikasi dari Profil di HP dulu.');
        }

        return self::SUCCESS;
    }
}
