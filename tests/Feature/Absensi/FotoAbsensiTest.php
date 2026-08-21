<?php

namespace Tests\Feature\Absensi;

use App\Enums\Role;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
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

    public function test_staff_hr_boleh_lihat_foto_siapa_pun(): void
    {
        // Laporan absensi terbuka untuk Staff HR & Admin Sistem; fotonya harus ikut,
        // kalau tidak semua thumbnail di layar laporan jadi 403.
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $a = $this->absensiDenganFoto($kar);

        $hr = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $hr->assignRole(Role::StaffHr->value);

        $this->actingAs($hr)->get("/absensi/foto/{$a->id}/masuk")->assertOk();
    }

    public function test_pemimpin_unit_boleh_lihat_foto_anggota_subtree(): void
    {
        $unit = OrgUnit::factory()->create();
        $anggota = Karyawan::factory()->staffUnit($unit)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit)->create();
        $a = $this->absensiDenganFoto($anggota);

        $userKoor = User::factory()->create(['karyawan_id' => $koor->id]);

        $this->actingAs($userKoor)->get("/absensi/foto/{$a->id}/masuk")->assertOk();
    }

    public function test_foto_null_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $a = Absensi::factory()->create(['karyawan_id' => $kar->id, 'jam_pulang' => null, 'foto_pulang_path' => null]);

        $this->actingAs($user)->get("/absensi/foto/{$a->id}/pulang")->assertForbidden();
    }
}
