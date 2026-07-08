<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PemeliharaanJatuhTempo;
use App\Support\PengingatPemeliharaan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('inventaris:kirim-pengingat')]
#[Description('Kirim pengingat pemeliharaan aset (H-14) ke tim pemilik')]
class KirimPengingatInventaris extends Command
{
    public function handle(): int
    {
        $terkirim = 0;

        foreach (PengingatPemeliharaan::semua() as $p) {
            $tim = $p->jadwal->aset->kategori->tim;

            // User ber-permission tim (langsung/via role). permission() dijamin ter-seed via RoleSeeder.
            $penerima = User::permission($tim->permission())->get();

            foreach ($penerima as $u) {
                $sudah = $u->notifications()
                    ->where('type', PemeliharaanJatuhTempo::class)
                    ->where('data->jadwal_id', $p->jadwal->id)
                    ->where('data->severity', $p->severity->value)
                    ->exists();
                if ($sudah) {
                    continue;
                }
                $u->notify(new PemeliharaanJatuhTempo($p->jadwal, $p->severity, $p->sisaHari));
                $terkirim++;
            }
        }

        $this->info("Selesai. {$terkirim} notifikasi terkirim.");

        return self::SUCCESS;
    }
}
