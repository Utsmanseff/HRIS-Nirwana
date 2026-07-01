<?php

namespace Tests\Feature;

use App\Enums\JenisKontrak;
use App\Enums\Role;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class KirimPengingatSdmTest extends TestCase
{
    use RefreshDatabase;

    private function buatHrd(): User
    {
        $this->seed(RoleSeeder::class);
        $hrd = User::factory()->create();
        $hrd->assignRole(Role::Hrd->value);

        return $hrd;
    }

    public function test_kirim_notif_kontrak_dan_sip_ke_hrd(): void
    {
        $hrd = $this->buatHrd();

        $karKontrak = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        Kontrak::factory()->for($karKontrak)->create([
            'jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => now()->subYear(),
            'tanggal_akhir' => now()->addDays(20),
        ]);
        Karyawan::factory()->create([
            'status' => StatusKaryawan::Aktif,
            'sip_nomor' => 'SIP/1/2026', 'sip_berlaku_akhir' => now()->addDays(10),
        ]);

        $this->artisan('sdm:kirim-pengingat')->assertSuccessful();

        $this->assertSame(2, $hrd->notifications()->count()); // 1 kontrak + 1 sip
    }

    public function test_dedup_tidak_kirim_ulang_severity_sama(): void
    {
        $hrd = $this->buatHrd();
        $kar = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        Kontrak::factory()->for($kar)->create([
            'jenis' => JenisKontrak::Pkwt, 'tanggal_mulai' => now()->subYear(),
            'tanggal_akhir' => now()->addDays(20),
        ]);

        $this->artisan('sdm:kirim-pengingat')->assertSuccessful();
        $this->artisan('sdm:kirim-pengingat')->assertSuccessful(); // jalan lagi

        $this->assertSame(1, $hrd->notifications()->count()); // tetap 1, tidak dobel
    }

    public function test_tanpa_hrd_tidak_error(): void
    {
        // tidak ada user HRD
        Karyawan::factory()->create([
            'status' => StatusKaryawan::Aktif,
            'sip_nomor' => 'SIP/9/2026', 'sip_berlaku_akhir' => now()->addDays(5),
        ]);
        $this->artisan('sdm:kirim-pengingat')->assertSuccessful();
        $this->assertSame(0, DatabaseNotification::count());
    }
}
