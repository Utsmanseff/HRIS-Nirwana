<?php

namespace App\Console\Commands;

use App\Models\TemplateJadwal;
use App\Support\ProsesPengganti;
use App\Support\TerapkanPola;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('absensi:bentuk-jadwal')]
#[Description('Auto-bentuk jadwal (non-destruktif) bulan berjalan + 2 bulan depan dari template pola')]
class BentukJadwalOtomatis extends Command
{
    public function handle(): int
    {
        $total = 0;
        $bulanTarget = collect([0, 1, 2])->map(fn ($i) => now()->startOfMonth()->addMonths($i));

        foreach (TemplateJadwal::with('baris')->get() as $pola) {
            foreach ($bulanTarget as $bln) {
                $total += TerapkanPola::untukPola($pola, $bln->year, $bln->month, null, timpa: false);
            }
        }

        // Lowongan terbuka: jadwal si nonaktif yang baru lahir harus ikut disalin
        // ke penggantinya. Idempoten, jadi aman dipanggil tiap kali.
        $salinan = ProsesPengganti::sinkronSemuaLowongan();

        $this->info("Selesai. {$total} jadwal terbentuk, {$salinan} salinan pengganti.");

        return self::SUCCESS;
    }
}
