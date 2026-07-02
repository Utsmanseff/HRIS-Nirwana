<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\KaryawanDetail;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class KaryawanKontrakTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_tambah_tahap_pkwt(): void
    {
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->create(['karyawan_id' => $kar->id, 'jenis' => 'percobaan', 'tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-03-31']);

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('formKontrakBaru')
            ->set('kJenis', 'pkwt')
            ->set('kMulai', '2026-04-01')
            ->set('kAkhir', '2027-03-31')
            ->set('kKeterangan', 'PKWT pertama')
            ->call('simpanKontrak')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kontrak', ['karyawan_id' => $kar->id, 'jenis' => 'pkwt', 'keterangan' => 'PKWT pertama']);
        $this->assertSame(2, $kar->kontrak()->count());
    }

    public function test_angkat_tetap_tanpa_tanggal_akhir(): void
    {
        $kar = Karyawan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('formKontrakBaru')
            ->set('kJenis', 'tetap')
            ->set('kMulai', '2026-07-01')
            ->set('kAkhir', '2027-07-01')
            ->call('simpanKontrak')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kontrak', ['karyawan_id' => $kar->id, 'jenis' => 'tetap', 'tanggal_akhir' => null]);
    }

    public function test_pkwt_wajib_tanggal_akhir(): void
    {
        $kar = Karyawan::factory()->create();

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('formKontrakBaru')
            ->set('kJenis', 'pkwt')
            ->set('kMulai', '2026-07-01')
            ->set('kAkhir', '')
            ->call('simpanKontrak')
            ->assertHasErrors(['kAkhir']);
    }
}
