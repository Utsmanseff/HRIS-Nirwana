<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Enums\Role;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KirimPengingatIzinTest extends TestCase
{
    use RefreshDatabase;

    private function buatHrd(): User
    {
        $this->seed(RoleSeeder::class);
        $hrd = User::factory()->create();
        $hrd->assignRole(Role::Hrd->value);

        return $hrd;
    }

    public function test_notif_sampai_ke_hrd_dan_karyawan(): void
    {
        $hrd = $this->buatHrd();
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        $userKar = User::factory()->create(['karyawan_id' => $kar->id]);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => JenisIzin::where('kode', KodeJenisIzin::Sip->value)->value('id'),
            'berlaku_akhir' => now()->addDays(10),
        ]);

        $this->artisan('sdm:kirim-pengingat')->assertSuccessful();

        $this->assertSame(1, $hrd->notifications()->count());
        $this->assertSame(1, $userKar->notifications()->count());
    }

    public function test_karyawan_tanpa_akun_hanya_hrd_yang_dapat(): void
    {
        $hrd = $this->buatHrd();
        $kar = Karyawan::factory()->create(['status' => 'aktif']); // tanpa User
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => JenisIzin::where('kode', KodeJenisIzin::Sip->value)->value('id'),
            'berlaku_akhir' => now()->addDays(10),
        ]);

        $this->artisan('sdm:kirim-pengingat')->assertSuccessful();

        $this->assertSame(1, $hrd->notifications()->count());
    }

    public function test_dedup_tidak_kirim_ulang(): void
    {
        $hrd = $this->buatHrd();
        $kar = Karyawan::factory()->create(['status' => 'aktif']);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => JenisIzin::where('kode', KodeJenisIzin::Sip->value)->value('id'),
            'berlaku_akhir' => now()->addDays(10),
        ]);

        $this->artisan('sdm:kirim-pengingat')->assertSuccessful();
        $this->artisan('sdm:kirim-pengingat')->assertSuccessful();

        $this->assertSame(1, $hrd->notifications()->count());
    }
}
