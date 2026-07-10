<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BersihkanFotoAbsensi extends Command
{
    protected $signature = 'absensi:bersihkan-foto';

    protected $description = 'Hapus foto absensi lebih dari 3 bulan (baris absensi tetap untuk laporan/penggajian).';

    public function handle(): int
    {
        $batas = now()->subMonths(3)->startOfDay();

        $query = Absensi::whereDate('tanggal_kerja', '<', $batas->toDateString())
            ->where(fn ($q) => $q->whereNotNull('foto_masuk_path')->orWhereNotNull('foto_pulang_path'));

        $jumlah = 0;
        $query->chunkById(200, function ($rows) use (&$jumlah) {
            foreach ($rows as $a) {
                foreach (['foto_masuk_path', 'foto_pulang_path'] as $kolom) {
                    if ($a->{$kolom}) {
                        Storage::disk('local')->delete($a->{$kolom});
                    }
                }
                $a->update(['foto_masuk_path' => null, 'foto_pulang_path' => null]);
                $jumlah++;
            }
        });

        $this->info("Foto absensi dibersihkan: {$jumlah} sesi (baris tetap utuh).");

        return self::SUCCESS;
    }
}
