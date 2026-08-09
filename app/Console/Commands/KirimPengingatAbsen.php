<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PengingatAbsenMasuk;
use App\Notifications\PengingatAbsenPulang;
use App\Support\PengingatAbsen;
use Closure;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('absensi:kirim-pengingat')]
#[Description('Kirim pengingat ke karyawan yang belum absen masuk / belum absen pulang')]
class KirimPengingatAbsen extends Command
{
    public function handle(): int
    {
        $terkirim = 0;

        foreach (PengingatAbsen::masukTerlewat() as $jadwal) {
            $terkirim += $this->kirim(
                $jadwal->karyawan->user,
                PengingatAbsenMasuk::class,
                'jadwal_id',
                $jadwal->id,
                fn () => new PengingatAbsenMasuk($jadwal),
            );
        }

        foreach (PengingatAbsen::pulangTerlewat() as $sesi) {
            $terkirim += $this->kirim(
                $sesi->karyawan->user,
                PengingatAbsenPulang::class,
                'absensi_id',
                $sesi->id,
                fn () => new PengingatAbsenPulang($sesi),
            );
        }

        $this->info("Selesai. {$terkirim} pengingat terkirim.");

        return self::SUCCESS;
    }

    /** Kirim bila user itu belum punya notif tipe+kunci yang sama. Mengembalikan 1 bila terkirim. */
    private function kirim(User $user, string $type, string $kunci, int $nilai, Closure $buat): int
    {
        $sudahAda = $user->notifications()
            ->where('type', $type)
            ->where("data->{$kunci}", $nilai)
            ->exists();

        if ($sudahAda) {
            return 0;
        }

        try {
            $user->notify($buat());
        } catch (Throwable $e) {
            // Satu langganan push rusak / endpoint lambat tak boleh menghentikan sisa antrean.
            // Kanal database jalan lebih dulu, jadi dedup tetap terjaga meski webpush gagal.
            report($e);

            return 0;
        }

        return 1;
    }
}
