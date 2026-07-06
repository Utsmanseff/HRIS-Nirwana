<?php

namespace Tests\Unit;

use App\Enums\JenisKontrak;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Support\SaldoCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SaldoCutiPeriodeValidTest extends TestCase
{
    use RefreshDatabase;

    public function test_periode_valid_untuk_karyawan_eligible(): void
    {
        Carbon::setTestNow('2027-06-01');
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create([
            'jenis' => JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        $periode = SaldoCuti::untuk($kar)->periodeValid();

        // Periode aktif mulai 2027-03-01, berikutnya 2028-03-01.
        $tanggal = array_map(fn ($c) => $c->toDateString(), $periode);
        $this->assertContains('2027-03-01', $tanggal);
        $this->assertContains('2028-03-01', $tanggal);
        Carbon::setTestNow();
    }

    public function test_periode_valid_kosong_bila_belum_eligible(): void
    {
        Carbon::setTestNow('2026-05-01');
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create([
            'jenis' => JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        // Baru 2 bulan kerja → belum eligible.
        $this->assertSame([], SaldoCuti::untuk($kar)->periodeValid());
        Carbon::setTestNow();
    }
}
