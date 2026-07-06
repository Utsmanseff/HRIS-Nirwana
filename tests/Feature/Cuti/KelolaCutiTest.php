<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Cuti\KelolaCuti;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
    }

    private function userHrd(): User
    {
        $kar = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $kar->id]);
        $u->assignRole('HRD');

        return $u;
    }

    public function test_non_hrd_ditolak(): void
    {
        $kar = Karyawan::factory()->create();
        $u = User::factory()->create(['karyawan_id' => $kar->id]);
        $u->assignRole('Karyawan');

        $this->actingAs($u)->get('/cuti/kelola')->assertForbidden();
    }

    public function test_hrd_bisa_buka(): void
    {
        $this->actingAs($this->userHrd())->get('/cuti/kelola')->assertOk();

        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->assertOk()
            ->assertSet('tab', 'hari-libur');
    }

    public function test_hari_libur_tambah_dan_hapus(): void
    {
        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->set('hlTanggal', '2026-08-17')
            ->set('hlNama', 'HUT RI')
            ->call('simpanHariLibur')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hari_libur', ['tanggal' => '2026-08-17 00:00:00', 'nama' => 'HUT RI']);

        $id = \App\Models\HariLibur::first()->id;
        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->call('hapusHariLibur', $id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('hari_libur', ['id' => $id]);
    }

    public function test_hari_libur_validasi(): void
    {
        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->set('hlTanggal', '')
            ->set('hlNama', '')
            ->call('simpanHariLibur')
            ->assertHasErrors(['hlTanggal', 'hlNama']);
    }

    public function test_jenis_cuti_edit_dan_toggle_aktif(): void
    {
        $jenis = \App\Models\JenisCuti::where('kode', 'izin_biasa')->first();

        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->call('editJenis', $jenis->id)
            ->assertSet('jcNama', $jenis->nama)
            ->set('jcNama', 'Izin Dinas')
            ->set('jcButuhLampiran', false)
            ->call('simpanJenis')
            ->assertHasNoErrors();

        $jenis->refresh();
        $this->assertSame('Izin Dinas', $jenis->nama);
        $this->assertFalse($jenis->butuh_lampiran);

        // Toggle aktif
        Livewire::actingAs($this->userHrd())->test(KelolaCuti::class)
            ->call('toggleAktif', $jenis->id);
        $this->assertFalse($jenis->refresh()->aktif);
    }

    public function test_penyesuaian_tambah_ke_periode_valid(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2027-06-01');
        $hrd = $this->userHrd();
        $kar = \App\Models\Karyawan::factory()->create(['nama_lengkap' => 'Wati Eligible']);
        \App\Models\Kontrak::factory()->for($kar)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        Livewire::actingAs($hrd)->test(KelolaCuti::class)
            ->set('tab', 'penyesuaian')
            ->set('psCari', 'Wati')
            ->call('pilihKaryawan', $kar->id)
            ->set('psPeriode', '2027-03-01')
            ->set('psDelta', 3)
            ->set('psAlasan', 'bonus loyalitas')
            ->call('simpanPenyesuaian')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('penyesuaian_saldo', [
            'karyawan_id' => $kar->id, 'periode_mulai' => '2027-03-01 00:00:00', 'delta' => 3, 'dibuat_oleh' => $hrd->id,
        ]);
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_penyesuaian_tolak_periode_di_luar_valid(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2027-06-01');
        $hrd = $this->userHrd();
        $kar = \App\Models\Karyawan::factory()->create();
        \App\Models\Kontrak::factory()->for($kar)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        Livewire::actingAs($hrd)->test(KelolaCuti::class)
            ->set('tab', 'penyesuaian')
            ->call('pilihKaryawan', $kar->id)
            ->set('psPeriode', '2020-01-01') // di luar periode valid
            ->set('psDelta', 2)
            ->set('psAlasan', 'ngawur')
            ->call('simpanPenyesuaian')
            ->assertHasErrors('psPeriode');

        $this->assertDatabaseCount('penyesuaian_saldo', 0);
        \Illuminate\Support\Carbon::setTestNow();
    }
}
