<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\KontrakAkanBerakhir;
use App\Notifications\SipAkanBerakhir;
use App\Support\PengingatKontrak;
use App\Support\PengingatSip;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

#[Signature('sdm:kirim-pengingat')]
#[Description('Kirim notifikasi pengingat kontrak & SIP yang mendekati/terlewat ke HRD')]
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

        foreach (PengingatSip::semua() as $p) {
            $terkirim += $this->kirim(
                $hrdSemua,
                SipAkanBerakhir::class,
                new SipAkanBerakhir($p->karyawan, $p->severity, $p->sisaHari),
                $p->karyawan->id,
                $p->severity->value,
            );
        }

        $this->info("Selesai. {$terkirim} notifikasi terkirim.");

        return self::SUCCESS;
    }

    /** Kirim ke tiap HRD yang belum punya notif tipe+karyawan+severity yang sama (dedup). */
    private function kirim(Collection $hrdSemua, string $type, Notification $notification, int $karyawanId, string $severity): int
    {
        $terkirim = 0;
        foreach ($hrdSemua as $hrd) {
            $sudahAda = $hrd->notifications()
                ->where('type', $type)
                ->where('data->karyawan_id', $karyawanId)
                ->where('data->severity', $severity)
                ->exists();
            if ($sudahAda) {
                continue;
            }
            $hrd->notify($notification);
            $terkirim++;
        }

        return $terkirim;
    }
}
