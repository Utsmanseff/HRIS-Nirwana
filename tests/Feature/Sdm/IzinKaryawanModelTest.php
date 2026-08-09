<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use Database\Seeders\JenisIzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IzinKaryawanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_karyawan_punya_banyak_izin_terbaru_dulu(): void
    {
        $this->seed(JenisIzinSeeder::class);
        $kar = Karyawan::factory()->create();
        $str = JenisIzin::where('kode', KodeJenisIzin::Str->value)->first();

        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id, 'jenis_izin_id' => $str->id,
            'berlaku_akhir' => '2027-01-01',
        ]);
        $baru = IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id, 'jenis_izin_id' => $str->id,
            'berlaku_akhir' => '2032-01-01',
        ]);

        $this->assertCount(2, $kar->izin);
        $this->assertSame($baru->id, $kar->izin->first()->id);
    }

    public function test_tanggal_di_cast_date(): void
    {
        $this->seed(JenisIzinSeeder::class);
        $izin = IzinKaryawan::factory()->create(['berlaku_akhir' => '2030-05-01']);

        $this->assertInstanceOf(Carbon::class, $izin->fresh()->berlaku_akhir);
    }
}
