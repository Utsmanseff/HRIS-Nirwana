<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Enums\Permission;
use App\Livewire\Sdm\JenisIzinKelola;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class JenisIzinKelolaTest extends TestCase
{
    use RefreshDatabase;

    private function aktorSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_simpan_ambang_dan_nama(): void
    {
        $this->actingAs($this->aktorSdm());
        $str = JenisIzin::where('kode', KodeJenisIzin::Str->value)->first();

        Livewire::test(JenisIzinKelola::class)
            ->call('edit', $str->id)
            ->set('nama', 'Surat Tanda Registrasi')
            ->set('ambangHari', 120)
            ->call('simpan')
            ->assertHasNoErrors();

        $str->refresh();
        $this->assertSame('Surat Tanda Registrasi', $str->nama);
        $this->assertSame(120, $str->ambang_hari);
        $this->assertSame(KodeJenisIzin::Str, $str->kode);   // kode tak ikut berubah
    }

    public function test_ambang_di_luar_batas_ditolak(): void
    {
        $this->actingAs($this->aktorSdm());
        $str = JenisIzin::where('kode', KodeJenisIzin::Str->value)->first();

        Livewire::test(JenisIzinKelola::class)
            ->call('edit', $str->id)
            ->set('ambangHari', 0)
            ->call('simpan')
            ->assertHasErrors(['ambangHari']);
    }

    public function test_nonaktifkan_jenis_membungkam_pengingatnya(): void
    {
        $this->actingAs($this->aktorSdm());
        $str = JenisIzin::where('kode', KodeJenisIzin::Str->value)->first();

        Livewire::test(JenisIzinKelola::class)
            ->call('edit', $str->id)
            ->set('aktif', false)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertFalse($str->refresh()->aktif);
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/sdm/jenis-izin')->assertForbidden();
    }
}
