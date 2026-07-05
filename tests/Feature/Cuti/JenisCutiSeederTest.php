<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Models\JenisCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JenisCutiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_empat_jenis_dengan_atribut_benar(): void
    {
        $this->seed(JenisCutiSeeder::class);

        $this->assertSame(4, JenisCuti::count());

        $tahunan = JenisCuti::where('kode', KodeJenisCuti::CutiTahunan->value)->first();
        $this->assertTrue((bool) $tahunan->potong_saldo);
        $this->assertFalse((bool) $tahunan->butuh_lampiran);
        $this->assertFalse((bool) $tahunan->boleh_backdate);

        $izin = JenisCuti::where('kode', KodeJenisCuti::IzinBiasa->value)->first();
        $this->assertFalse((bool) $izin->potong_saldo);
        $this->assertTrue((bool) $izin->butuh_lampiran);
        $this->assertSame('potong_gaji_jasa', $izin->efek_penggajian);

        $sakit = JenisCuti::where('kode', KodeJenisCuti::CutiSakit->value)->first();
        $this->assertTrue((bool) $sakit->butuh_lampiran);
        $this->assertTrue((bool) $sakit->boleh_backdate);
    }

    public function test_seed_idempoten(): void
    {
        $this->seed(JenisCutiSeeder::class);
        $this->seed(JenisCutiSeeder::class);
        $this->assertSame(4, JenisCuti::count());
    }
}
