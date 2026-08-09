<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Enums\Permission;
use App\Livewire\Sdm\KaryawanDetail;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class TabPerizinanTest extends TestCase
{
    use RefreshDatabase;

    private function aktorSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_tambah_izin(): void
    {
        $this->actingAs($this->aktorSdm());
        $kar = Karyawan::factory()->create();
        $str = JenisIzin::where('kode', KodeJenisIzin::Str->value)->first();

        Livewire::test(KaryawanDetail::class, ['karyawan' => $kar])
            ->set('izJenisId', $str->id)
            ->set('izNomor', 'STR/77/2026')
            ->set('izMulai', '2026-01-01')
            ->set('izAkhir', '2031-01-01')
            ->call('simpanIzin')
            ->assertHasNoErrors();

        $this->assertSame(1, IzinKaryawan::where('karyawan_id', $kar->id)->count());
    }

    public function test_akhir_harus_setelah_mulai(): void
    {
        $this->actingAs($this->aktorSdm());
        $kar = Karyawan::factory()->create();
        $str = JenisIzin::where('kode', KodeJenisIzin::Str->value)->first();

        Livewire::test(KaryawanDetail::class, ['karyawan' => $kar])
            ->set('izJenisId', $str->id)
            ->set('izMulai', '2030-01-01')
            ->set('izAkhir', '2029-01-01')
            ->call('simpanIzin')
            ->assertHasErrors(['izAkhir']);
    }

    public function test_hapus_izin(): void
    {
        $this->actingAs($this->aktorSdm());
        $kar = Karyawan::factory()->create();
        $izin = IzinKaryawan::factory()->create(['karyawan_id' => $kar->id]);

        Livewire::test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('hapusIzin', $izin->id);

        $this->assertSame(0, IzinKaryawan::where('karyawan_id', $kar->id)->count());
    }

    public function test_tak_bisa_hapus_izin_karyawan_lain(): void
    {
        $this->actingAs($this->aktorSdm());
        $kar = Karyawan::factory()->create();
        $lain = IzinKaryawan::factory()->create();   // milik karyawan lain

        Livewire::test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('hapusIzin', $lain->id);

        $this->assertSame(1, IzinKaryawan::whereKey($lain->id)->count());
    }
}
