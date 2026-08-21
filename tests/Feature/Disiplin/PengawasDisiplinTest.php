<?php

namespace Tests\Feature\Disiplin;

use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Enums\RutePengawas;
use App\Enums\StatusSanksi;
use App\Livewire\Disiplin\UsulDisiplin;
use App\Livewire\Sdm\OrgStruktur;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PengawasDisiplinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Direktorat > Penunjang > Farmasi, plus unit pengawas berisi SATU orang tanpa anggota. */
    private function hierarki(): array
    {
        $dir = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $bidang = OrgUnit::create(['nama' => 'Penunjang', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $dir->id]);
        $unit = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $bidang->id]);
        $unitSpi = OrgUnit::create(['nama' => 'SPI', 'tipe' => OrgUnitTipe::Bagian->value, 'parent_id' => $dir->id]);

        $direktur = Karyawan::factory()->pimpinanUnit($dir, 4)->create();
        $kabid = Karyawan::factory()->pimpinanUnit($bidang, 3)->create();
        $koor = Karyawan::factory()->pimpinanUnit($unit, 2)->create();
        $staf = Karyawan::factory()->staffUnit($unit)->create();
        $spi = Karyawan::factory()->pimpinanUnit($unitSpi, 3)->create();

        $hrd = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $direktur->id])->assignRole(Role::Direktur->value);
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(Role::Hrd->value);

        return compact('dir', 'bidang', 'unit', 'unitSpi', 'direktur', 'kabid', 'koor', 'staf', 'spi', 'hrd');
    }

    private function jadikanPengawas(Karyawan $kar, RutePengawas $rute = RutePengawas::LangsungHrd): Karyawan
    {
        $kar->jabatan->update(['rute_pengawas' => $rute]);

        return $kar->fresh();
    }

    private function login(Karyawan $kar): User
    {
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_gate_tertutup_untuk_jabatan_pengawas_sebelum_ditandai(): void
    {
        // Unit SPI berisi satu orang tanpa anggota → punyaBawahan() false.
        $h = $this->hierarki();
        $user = $this->login($h['spi']);

        $this->assertFalse($user->can('usul-disiplin'));
    }

    public function test_gate_terbuka_untuk_jabatan_pengawas_walau_tanpa_bawahan(): void
    {
        $h = $this->hierarki();
        $spi = $this->jadikanPengawas($h['spi']);
        $user = $this->login($spi);

        $this->assertTrue($user->can('usul-disiplin'));
    }

    public function test_pengawas_tetap_tak_boleh_menyetujui(): void
    {
        // Pengawas merekomendasikan, bukan memutus.
        $h = $this->hierarki();
        $user = $this->login($this->jadikanPengawas($h['spi']));

        $this->assertFalse($user->can('approve-disiplin'));
        $this->assertFalse($user->can('approve-cuti'));
    }

    public function test_pengawas_menjangkau_karyawan_di_luar_unitnya(): void
    {
        $h = $this->hierarki();
        $this->login($this->jadikanPengawas($h['spi']));

        Livewire::test(UsulDisiplin::class)
            ->set('cari', $h['staf']->nama_lengkap)
            ->assertSee($h['staf']->nama_lengkap);
    }

    public function test_pengawas_tak_bisa_mengusulkan_direktur(): void
    {
        $h = $this->hierarki();
        $this->login($this->jadikanPengawas($h['spi']));

        Livewire::test(UsulDisiplin::class)
            ->set('cari', $h['direktur']->nama_lengkap)
            ->assertDontSee($h['direktur']->nama_lengkap);
    }

    public function test_sesama_pengawas_boleh_saling_usul(): void
    {
        $h = $this->hierarki();
        $unitSpi2 = OrgUnit::create(['nama' => 'SPI Dua', 'tipe' => OrgUnitTipe::Bagian->value, 'parent_id' => $h['dir']->id]);
        $spi2 = $this->jadikanPengawas(Karyawan::factory()->pimpinanUnit($unitSpi2, 3)->create());
        $this->login($this->jadikanPengawas($h['spi']));

        Livewire::test(UsulDisiplin::class)
            ->set('cari', $spi2->nama_lengkap)
            ->assertSee($spi2->nama_lengkap);
    }

    public function test_non_pengawas_tetap_terbatas_pada_turunan_unitnya(): void
    {
        $h = $this->hierarki();
        $unitLain = OrgUnit::create(['nama' => 'Gizi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $h['bidang']->id]);
        $luar = Karyawan::factory()->staffUnit($unitLain)->create();
        $this->login($h['koor']);

        Livewire::test(UsulDisiplin::class)
            ->set('cari', $luar->nama_lengkap)
            ->assertDontSee($luar->nama_lengkap);
    }

    public function test_usulan_pengawas_tersimpan_dengan_rantai_langsung_hrd(): void
    {
        $h = $this->hierarki();
        $this->login($this->jadikanPengawas($h['spi']));

        Livewire::test(UsulDisiplin::class)
            ->call('pilihKaryawan', $h['staf']->id)
            ->set('uraian', 'Terlambat berulang')
            ->set('tanggalKejadian', now()->subDay()->toDateString())
            ->set('tingkat', '1')
            ->call('simpan');

        $sanksi = SanksiDisiplin::where('karyawan_id', $h['staf']->id)->firstOrFail();
        $this->assertSame(StatusSanksi::Diajukan, $sanksi->status);
        $this->assertSame(
            [$h['hrd']->id, $h['direktur']->id],
            $sanksi->approval()->orderBy('urutan')->pluck('approver_id')->all()
        );
    }

    public function test_usulan_ditolak_saat_belum_ada_pemegang_hrd_dan_direktur(): void
    {
        // Rantai kosong dulu tersimpan diam-diam: nol penyetuju, nol notifikasi, buntu.
        $h = $this->hierarki();
        User::whereIn('karyawan_id', [$h['direktur']->id, $h['hrd']->id])->get()
            ->each(fn (User $u) => $u->syncRoles([]));

        $this->login($this->jadikanPengawas($h['spi']));

        Livewire::test(UsulDisiplin::class)
            ->call('pilihKaryawan', $h['staf']->id)
            ->set('uraian', 'Terlambat berulang')
            ->set('tanggalKejadian', now()->subDay()->toDateString())
            ->set('tingkat', '1')
            ->call('simpan')
            ->assertHasErrors('karyawanId');

        $this->assertSame(0, SanksiDisiplin::count());
    }

    public function test_panel_jabatan_menyimpan_rute_pengawas(): void
    {
        $h = $this->hierarki();
        $admin = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $admin->assignRole(Role::AdminSistem->value);
        $this->actingAs($admin);

        $jabatan = $h['spi']->jabatan;

        Livewire::test(OrgStruktur::class)
            ->set('jabatanUnitId', $h['unitSpi']->id)
            ->call('editJabatanPimpinan', $jabatan->id)
            ->set('jpNama', 'SPI Pelayanan')
            ->set('jpRute', RutePengawas::LangsungHrd->value)
            ->call('simpanJabatanPimpinan');

        $jabatan->refresh();
        $this->assertSame('SPI Pelayanan', $jabatan->nama);
        $this->assertSame(RutePengawas::LangsungHrd, $jabatan->rute_pengawas);
    }

    public function test_panel_jabatan_bisa_mencabut_status_pengawas(): void
    {
        $h = $this->hierarki();
        $jabatan = $this->jadikanPengawas($h['spi'])->jabatan;
        $admin = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $admin->assignRole(Role::AdminSistem->value);
        $this->actingAs($admin);

        Livewire::test(OrgStruktur::class)
            ->set('jabatanUnitId', $h['unitSpi']->id)
            ->call('editJabatanPimpinan', $jabatan->id)
            ->set('jpRute', '')
            ->call('simpanJabatanPimpinan');

        $this->assertNull($jabatan->refresh()->rute_pengawas);
    }

    public function test_hrd_buat_langsung_juga_ditolak_saat_rantai_kosong(): void
    {
        // Jalur HRD membuat sanksi langsung hanya bergantung pada Direktur sebagai
        // penutup rantai; tanpa itu sanksinya terbit tanpa penyetuju.
        $h = $this->hierarki();
        User::where('karyawan_id', $h['direktur']->id)->get()->each(fn (User $u) => $u->syncRoles([]));

        $hrdUser = User::where('karyawan_id', $h['hrd']->id)->firstOrFail();
        $this->actingAs($hrdUser);

        Livewire::test(\App\Livewire\Disiplin\KelolaDisiplin::class)
            ->call('pilihKaryawan', $h['staf']->id)
            ->set('uraian', 'Pelanggaran berat')
            ->set('tanggalKejadian', now()->subDay()->toDateString())
            ->set('tingkat', '1')
            ->set('nomorSurat', 'SP/001/2026')
            ->call('simpan')
            ->assertHasErrors('karyawanId');

        $this->assertSame(0, SanksiDisiplin::count());
    }
}
