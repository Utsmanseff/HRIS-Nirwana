<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BersihkanFotoAbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hapus_foto_lebih_dari_3_bulan_tapi_baris_tetap(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2026-07-10');
        $kar = Karyawan::factory()->create();

        $lama = "absensi/{$kar->id}/lama-masuk.webp";
        $lamaP = "absensi/{$kar->id}/lama-pulang.webp";
        Storage::disk('local')->put($lama, 'x');
        Storage::disk('local')->put($lamaP, 'x');
        $a = Absensi::factory()->create([
            'karyawan_id' => $kar->id,
            'tanggal_kerja' => '2026-03-01', // > 3 bulan
            'foto_masuk_path' => $lama,
            'foto_pulang_path' => $lamaP,
        ]);

        $baru = "absensi/{$kar->id}/baru.webp";
        Storage::disk('local')->put($baru, 'x');
        $b = Absensi::factory()->create([
            'karyawan_id' => $kar->id,
            'tanggal_kerja' => '2026-07-01', // < 3 bulan
            'foto_masuk_path' => $baru,
        ]);

        $this->artisan('absensi:bersihkan-foto')->assertSuccessful();

        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertMissing($lamaP);
        Storage::disk('local')->assertExists($baru);

        $a->refresh();
        $b->refresh();
        $this->assertNull($a->foto_masuk_path);
        $this->assertNull($a->foto_pulang_path);
        $this->assertDatabaseHas('absensi', ['id' => $a->id]); // baris tetap
        $this->assertSame($baru, $b->foto_masuk_path);

        Carbon::setTestNow();
    }
}
