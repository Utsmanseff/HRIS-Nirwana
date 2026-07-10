<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoAbsensiTest extends TestCase
{
    use RefreshDatabase;

    private function absensiDenganFoto(Karyawan $kar): Absensi
    {
        Storage::fake('local');
        $path = "absensi/{$kar->id}/foto.webp";
        Storage::disk('local')->put($path, 'bytes-webp');

        return Absensi::factory()->create([
            'karyawan_id' => $kar->id,
            'foto_masuk_path' => $path,
            'jam_pulang' => null,
        ]);
    }

    public function test_pemilik_boleh_lihat_foto_masuk(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $a = $this->absensiDenganFoto($kar);

        $this->actingAs($user)->get("/absensi/foto/{$a->id}/masuk")->assertOk();
    }

    public function test_orang_lain_dilarang(): void
    {
        $kar = Karyawan::factory()->create();
        $a = $this->absensiDenganFoto($kar);
        $lain = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($lain)->get("/absensi/foto/{$a->id}/masuk")->assertForbidden();
    }

    public function test_foto_null_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $a = Absensi::factory()->create(['karyawan_id' => $kar->id, 'jam_pulang' => null, 'foto_pulang_path' => null]);

        $this->actingAs($user)->get("/absensi/foto/{$a->id}/pulang")->assertForbidden();
    }
}
