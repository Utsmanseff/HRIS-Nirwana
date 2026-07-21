<?php

namespace Tests\Feature\Absensi;

use App\Livewire\Absensi\JadwalKelola;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKelolaTest extends TestCase
{
    use RefreshDatabase;

    private function koordinator(): User
    {
        $bidang = OrgUnit::factory()->create(['tipe' => 'bidang', 'parent_id' => null]);
        $unit = OrgUnit::factory()->create(['tipe' => 'unit', 'parent_id' => $bidang->id]);
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 2]);
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id]);

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_render_untuk_koordinator_memilih_unit_pimpinan(): void
    {
        $user = $this->koordinator();

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->assertOk()
            ->assertSet('tab', 'shift')
            ->assertSee('Shift Unit');
    }

    public function test_unit_terpilih_default_unit_pertama_dipimpin(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->assertSet('unitId', $unitId);
    }

    public function test_ganti_tab(): void
    {
        Livewire::actingAs($this->koordinator())->test(JadwalKelola::class)
            ->call('gantiTab', 'jadwal')
            ->assertSet('tab', 'jadwal');
    }

    public function test_tambah_shift_untuk_unit(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('sNama', 'Pagi')->set('sKode', 'P')->set('sWarna', '#16A34A')
            ->set('sMulai', '07:00')->set('sSelesai', '14:00')->set('sToleransi', 15)
            ->call('simpanShift')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shift', ['org_unit_id' => $unitId, 'kode' => 'P', 'nama' => 'Pagi']);
    }

    public function test_edit_shift(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unitId, 'kode' => 'P', 'toleransi_telat' => 10]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('editShift', $shift->id)
            ->assertSet('sToleransi', 10)
            ->set('sToleransi', 20)
            ->call('simpanShift');

        $this->assertDatabaseHas('shift', ['id' => $shift->id, 'toleransi_telat' => 20]);
    }

    public function test_kode_shift_unik_per_unit(): void
    {
        $user = $this->koordinator();
        $unitId = $user->karyawan->unitDipimpin()->first()->id;
        \App\Models\Shift::factory()->create(['org_unit_id' => $unitId, 'kode' => 'P']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('sNama', 'Pagi 2')->set('sKode', 'P')->set('sWarna', '#111111')
            ->set('sMulai', '08:00')->set('sSelesai', '15:00')->set('sToleransi', 10)
            ->call('simpanShift')
            ->assertHasErrors('sKode');
    }

    private function staffDi(OrgUnit $unit): Karyawan
    {
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);

        return Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id]);
    }

    public function test_simpan_template_pola_rotasi_dari_grid_kode(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('tplMode', 'rotasi')
            ->set('tplJangkar', '2026-07-01')
            ->set('tplPanjang', 2)
            ->set("polaGrid.{$staff->id}.0", 'P')
            ->set("polaGrid.{$staff->id}.1", 'L')
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('template_jadwal', ['org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01 00:00:00', 'mode' => 'rotasi']);
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 1, 'shift_id' => null]);
    }

    public function test_kode_tak_dikenal_ditolak(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('tplMode', 'rotasi')
            ->set('tplJangkar', '2026-07-01')->set('tplPanjang', 1)
            ->set("polaGrid.{$staff->id}.0", 'XX')
            ->call('simpanTemplate')
            ->assertHasErrors('polaGrid');
    }

    public function test_simpan_template_mingguan_kunci_7_slot(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('tplMode', 'mingguan')
            ->set("polaGrid.{$staff->id}.0", 'P')   // Senin
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('template_jadwal', ['org_unit_id' => $unit->id, 'mode' => 'mingguan']);
        $this->assertSame(7, \App\Models\PolaJadwal::where('karyawan_id', $staff->id)->count());
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('pola_jadwal', ['karyawan_id' => $staff->id, 'posisi' => 6, 'shift_id' => null]);
    }

    public function test_panjang_siklus_beda_per_karyawan(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $a = $this->staffDi($unit);
        $b = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('tplMode', 'rotasi')
            ->set('tplJangkar', '2026-07-01')
            ->set("panjangBaris.{$a->id}", 2)
            ->set("panjangBaris.{$b->id}", 3)
            ->set("polaGrid.{$a->id}.0", 'P')->set("polaGrid.{$a->id}.1", 'L')
            ->set("polaGrid.{$b->id}.0", 'P')->set("polaGrid.{$b->id}.1", 'P')->set("polaGrid.{$b->id}.2", 'L')
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertSame(2, \App\Models\PolaJadwal::where('karyawan_id', $a->id)->count());
        $this->assertSame(3, \App\Models\PolaJadwal::where('karyawan_id', $b->id)->count());
    }

    public function test_mount_di_tab_template_memuat_pola_tersimpan(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        $tpl = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => null]);
        \App\Models\PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $staff->id, 'posisi' => 1, 'shift_id' => null]);

        // Load LANGSUNG dengan tab=template (bukan lewat gantiTab).
        $c = Livewire::actingAs($user)->withQueryParams(['tab' => 'template'])->test(JadwalKelola::class);

        $this->assertArrayHasKey($staff->id, $c->get('polaGrid'));
        $this->assertSame(2, $c->get('panjangBaris')[$staff->id]);
    }

    public function test_tambah_dan_hapus_baris_karyawan(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $staff = $this->staffDi($unit);

        $c = Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $staff->id)
            ->assertSet("panjangBaris.{$staff->id}", 7);

        $this->assertArrayHasKey($staff->id, $c->get('polaGrid'));

        $c->call('hapusBaris', $staff->id);
        $this->assertArrayNotHasKey($staff->id, $c->get('polaGrid'));
    }

    public function test_hapus_baris_lalu_simpan_menghapus_pola(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        $tpl = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => null]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')   // muat → grid berisi staff
            ->call('hapusBaris', $staff->id)  // opt-out
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertSame(0, \App\Models\PolaJadwal::where('karyawan_id', $staff->id)->count());
    }

    public function test_tambah_karyawan_luar_kelolaan_ditolak(): void
    {
        $user = $this->koordinator();
        $unitLain = OrgUnit::factory()->create(['tipe' => 'unit', 'parent_id' => null]);
        $luar = $this->staffDi($unitLain);

        $c = Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $luar->id);

        $this->assertArrayNotHasKey($luar->id, $c->get('polaGrid'));
    }

    public function test_tambah_kurang_kolom_ubah_panjang_satu_baris(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $staff->id)
            ->assertSet("panjangBaris.{$staff->id}", 7)
            ->call('tambahKolom', $staff->id)
            ->assertSet("panjangBaris.{$staff->id}", 8)
            ->call('kurangKolom', $staff->id)
            ->call('kurangKolom', $staff->id)
            ->assertSet("panjangBaris.{$staff->id}", 6);
    }

    public function test_kurang_kolom_batas_bawah_satu(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $staff->id)
            ->set("panjangBaris.{$staff->id}", 1)
            ->call('kurangKolom', $staff->id)
            ->assertSet("panjangBaris.{$staff->id}", 1);
    }

    public function test_set_sel_jadwal_membuat_dan_menghapus(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        $c = Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'jadwal')
            ->set('tahun', 2026)->set('bulan', 7)
            ->call('setSel', $staff->id, 5, 'P');

        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $staff->id, 'tanggal' => '2026-07-05 00:00:00', 'shift_id' => $shift->id]);

        $c->call('setSel', $staff->id, 5, '');   // kosongkan → hapus
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $staff->id, 'tanggal' => '2026-07-05 00:00:00']);
    }

    public function test_terapkan_pola_mengisi_bulan(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        $tpl = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'jadwal')->set('tahun', 2026)->set('bulan', 7)
            ->call('terapkanPola');

        $this->assertSame(31, \App\Models\Jadwal::where('karyawan_id', $staff->id)->count()); // siklus [P] len1 → tiap hari
    }

    /** Dua shift unit yang TIDAK bentrok: Malam 00:00-08:00 & Sore 16:00-00:00. */
    private function duaShiftTakBentrok(OrgUnit $unit): array
    {
        return [
            \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'M', 'nama' => 'Malam',
                'jam_mulai' => '00:00:00', 'jam_selesai' => '08:00:00']),
            \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'S', 'nama' => 'Sore',
                'jam_mulai' => '16:00:00', 'jam_selesai' => '00:00:00']),
        ];
    }

    public function test_set_sel_dua_kode_membuat_dua_jadwal(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        [$malam, $sore] = $this->duaShiftTakBentrok($unit);
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('tahun', 2026)->set('bulan', 7)
            ->call('setSel', $staff->id, 20, 'M,S')
            ->assertHasNoErrors();

        $this->assertSame(2, \App\Models\Jadwal::where('karyawan_id', $staff->id)->whereDate('tanggal', '2026-07-20')->count());
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $staff->id, 'tanggal' => '2026-07-20 00:00:00', 'shift_id' => $malam->id]);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $staff->id, 'tanggal' => '2026-07-20 00:00:00', 'shift_id' => $sore->id]);
    }

    public function test_set_sel_mengurangi_kode_menghapus_baris_yang_hilang(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        [$malam, $sore] = $this->duaShiftTakBentrok($unit);
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('tahun', 2026)->set('bulan', 7)
            ->call('setSel', $staff->id, 20, 'M,S')
            ->call('setSel', $staff->id, 20, 'S')
            ->assertHasNoErrors();

        $this->assertSame(1, \App\Models\Jadwal::where('karyawan_id', $staff->id)->whereDate('tanggal', '2026-07-20')->count());
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $staff->id, 'tanggal' => '2026-07-20 00:00:00', 'shift_id' => $malam->id]);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $staff->id, 'tanggal' => '2026-07-20 00:00:00', 'shift_id' => $sore->id]);
    }

    public function test_set_sel_kosong_menghapus_semua_baris_hari_itu(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $this->duaShiftTakBentrok($unit);
        $staff = $this->staffDi($unit);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('tahun', 2026)->set('bulan', 7)
            ->call('setSel', $staff->id, 20, 'M,S')
            ->call('setSel', $staff->id, 20, '');

        $this->assertSame(0, \App\Models\Jadwal::where('karyawan_id', $staff->id)->whereDate('tanggal', '2026-07-20')->count());
    }

    public function test_set_sel_menolak_shift_yang_jamnya_bentrok(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P', 'nama' => 'Pagi',
            'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00']);
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'D', 'nama' => 'Siang',
            'jam_mulai' => '13:00:00', 'jam_selesai' => '21:00:00']);
        $staff = $this->staffDi($unit);

        $komponen = Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('tahun', 2026)->set('bulan', 7)
            ->call('setSel', $staff->id, 20, 'P,D')
            ->assertHasErrors('jadwal');

        $this->assertStringContainsString('bentrok', $komponen->errors()->first('jadwal'));

        $this->assertSame(0, \App\Models\Jadwal::where('karyawan_id', $staff->id)->whereDate('tanggal', '2026-07-20')->count());
    }

    public function test_set_sel_kode_tak_dikenal_tak_mengubah_data(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $this->duaShiftTakBentrok($unit);
        $staff = $this->staffDi($unit);

        $komponen = Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('tahun', 2026)->set('bulan', 7)
            ->call('setSel', $staff->id, 20, 'M,ZZ')
            ->assertHasErrors('jadwal');

        $this->assertStringContainsString('"ZZ" tidak dikenal', $komponen->errors()->first('jadwal'));

        $this->assertSame(0, \App\Models\Jadwal::where('karyawan_id', $staff->id)->whereDate('tanggal', '2026-07-20')->count());
    }
}
