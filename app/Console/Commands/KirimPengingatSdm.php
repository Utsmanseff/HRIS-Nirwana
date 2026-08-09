<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\IzinAkanBerakhir;
use App\Notifications\KontrakAkanBerakhir;
use App\Support\PengingatIzin;
use App\Support\PengingatKontrak;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

#[Signature('sdm:kirim-pengingat')]
#[Description('Kirim notifikasi pengingat kontrak & perizinan yang mendekati/terlewat')]
class KirimPengingatSdm extends Command
{
    public function handle(): int
    {
        // whereHas (bukan User::role) agar tak melempar bila role belum ter-seed — command
        // terjadwal harus aman di sistem baru/tanpa HRD.
        $hrdSemua = User::whereHas('roles', fn ($q) => $q->where('name', Role::Hrd->value))->get();
        if ($hrdSemua->isEmpty()) {
            $this->info('Tidak ada user HRD. Lewati.');

            return self::SUCCESS;
        }

        $terkirim = 0;

        foreach (PengingatKontrak::semua() as $p) {
            $terkirim += $this->kirim(
                $hrdSemua,
                KontrakAkanBerakhir::class,
                new KontrakAkanBerakhir($p->karyawan, $p->severity, $p->sisaHari),
                $p->karyawan->id,
                $p->severity->value,
            );
        }

        foreach (PengingatIzin::semua() as $p) {
            // Penerima izin = HRD + karyawan ybs (dialah yang mengurus perpanjangan).
            $penerima = $hrdSemua->all();
            $userKaryawan = $p->izin->karyawan->user;
            if ($userKaryawan) {
                $penerima[] = $userKaryawan;
            }

            $terkirim += $this->kirim(
                collect($penerima),
                IzinAkanBerakhir::class,
                new IzinAkanBerakhir($p->izin, $p->severity, $p->sisaHari),
                $p->izin->karyawan_id,
                $p->severity->value,
            );
        }

        $this->info("Selesai. {$terkirim} notifikasi terkirim.");

        return self::SUCCESS;
    }

    /** Kirim ke tiap penerima yang belum punya notif tipe+karyawan+severity yang sama (dedup). */
    private function kirim(Collection $penerima, string $type, Notification $notification, int $karyawanId, string $severity): int
    {
        $terkirim = 0;
        foreach ($penerima as $user) {
            $kueri = $user->notifications()
                ->where('type', $type)
                ->where('data->karyawan_id', $karyawanId)
                ->where('data->severity', $severity);

            // Satu karyawan bisa punya STR & SIP sekaligus dengan severity sama —
            // tanpa kunci jenis, notif kedua tertelan dedup.
            if ($notification instanceof IzinAkanBerakhir) {
                $kueri->where('data->kode_izin', $notification->izin->jenis->kode->value);
            }

            if ($kueri->exists()) {
                continue;
            }

            $user->notify($notification);
            $terkirim++;
        }

        return $terkirim;
    }
}
