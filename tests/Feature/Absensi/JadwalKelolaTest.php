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
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
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
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
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
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
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

        $tpl = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'jadwal')->set('tahun', 2026)->set('bulan', 7)
            ->call('terapkanPola', $tpl->id);

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

    public function test_buat_pola_baru_untuk_unit(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('pNama', 'Pola CS IGD')
            ->call('buatPola')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('template_jadwal', ['org_unit_id' => $unit->id, 'nama' => 'Pola CS IGD']);
    }

    public function test_buat_pola_menolak_nama_kembar_di_unit_sama(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola CS IGD', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('pNama', 'Pola CS IGD')
            ->call('buatPola')
            ->assertHasErrors('pNama');

        $this->assertSame(1, \App\Models\TemplateJadwal::where('org_unit_id', $unit->id)->count());
    }

    public function test_buat_pola_langsung_jadi_pola_aktif(): void
    {
        $user = $this->koordinator();

        $komponen = Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'template')
            ->set('pNama', 'Pola CS Poli')
            ->call('buatPola');

        $baru = \App\Models\TemplateJadwal::where('nama', 'Pola CS Poli')->first();
        $komponen->assertSet('polaId', $baru->id);
    }

    public function test_simpan_pola_menulis_baris_ke_pola_aktif(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->set('tplJangkar', '2026-07-01')
            ->set("polaGrid.{$staff->id}.0", 'P')
            ->set("polaGrid.{$staff->id}.1", 'L')
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pola_jadwal', ['template_id' => $pola->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('pola_jadwal', ['template_id' => $pola->id, 'karyawan_id' => $staff->id, 'posisi' => 1, 'shift_id' => null]);
    }

    public function test_ganti_pola_memuat_grid_pola_itu(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        $polaA = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);
        $polaB = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola B', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $polaB->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $polaA->id)
            ->call('gantiTab', 'template')
            ->assertSet("polaGrid.{$staff->id}", null)
            ->call('gantiPola', $polaB->id)
            ->assertSet("polaGrid.{$staff->id}.0", 'P');
    }

    public function test_ubah_nama_pola(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola Lama', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->call('bukaFormUbahNama')
            ->assertSet('pNama', 'Pola Lama')
            ->assertSet('modeFormPola', 'ubah')
            ->set('pNama', 'Pola Baru')
            ->call('ubahNamaPola')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('template_jadwal', ['id' => $pola->id, 'nama' => 'Pola Baru']);
    }

    public function test_hapus_pola_menyisakan_jadwal_yang_sudah_terbentuk(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $pola->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        \App\Models\Jadwal::create(['karyawan_id' => $staff->id, 'tanggal' => '2026-07-05', 'shift_id' => $shift->id]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->call('hapusPola');

        $this->assertDatabaseMissing('template_jadwal', ['id' => $pola->id]);
        $this->assertDatabaseMissing('pola_jadwal', ['template_id' => $pola->id]);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $staff->id, 'tanggal' => '2026-07-05 00:00:00', 'shift_id' => $shift->id]);
    }

    public function test_cari_anggota_menyaring_kelolaan_menurut_nama(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);
        Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id, 'nama_lengkap' => 'Siti Aminah']);
        Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id, 'nama_lengkap' => 'Budi Santoso']);

        // tab-jadwal (pane tersembunyi) menampilkan SEMUA kelolaan, jadi assertDontSee
        // pada HTML bocor. Uji langsung koleksi hasil pencarian yang dirender.
        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->set('cariAnggota', 'Siti')
            ->assertSee('Siti Aminah')
            ->assertViewHas('hasilCariAnggota', fn ($hasil) => $hasil->pluck('nama_lengkap')->all() === ['Siti Aminah']);
    }

    public function test_tambah_anggota_yang_sudah_berpola_memindahkannya(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $staff = $this->staffDi($unit);

        $polaA = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $polaA->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        $polaB = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola B', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $polaB->id)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $staff->id)
            ->set("polaGrid.{$staff->id}.0", 'P')
            ->set('tplJangkar', '2026-07-01')
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        // Hanya satu keanggotaan yang tersisa, dan itu di pola B.
        $this->assertSame(0, \App\Models\PolaJadwal::where('template_id', $polaA->id)->where('karyawan_id', $staff->id)->count());
        $this->assertGreaterThan(0, \App\Models\PolaJadwal::where('template_id', $polaB->id)->where('karyawan_id', $staff->id)->count());
    }

    public function test_anggota_pola_lain_ditandai_saat_dicari(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $staff = $this->staffDi($unit);
        $staff->update(['nama_lengkap' => 'Siti Aminah']);

        $polaA = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $polaA->id, 'karyawan_id' => $staff->id, 'posisi' => 0, 'shift_id' => null]);
        $polaB = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola B', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $polaB->id)
            ->call('gantiTab', 'template')
            ->set('cariAnggota', 'Siti')
            ->assertSee('sudah di Pola A')     // penanda pola asal
            ->assertSee('Tukar dengan…')       // aksi tukar antar pola tersedia
            ->assertViewHas('polaLainPeta', fn ($peta) => ($peta[$staff->id] ?? null) === 'Pola A');
    }

    public function test_tukar_baris_menukar_siklus_dua_karyawan(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'S']);
        $a = $this->staffDi($unit);
        $b = $this->staffDi($unit);
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $a->id)
            ->call('tambahKaryawan', $b->id)
            ->set("polaGrid.{$a->id}.0", 'P')
            ->set("polaGrid.{$b->id}.0", 'S')
            ->call('pilihTukar', $a->id)
            ->call('tukarBaris', $b->id)
            ->assertSet("polaGrid.{$a->id}.0", 'S')
            ->assertSet("polaGrid.{$b->id}.0", 'P')
            ->assertSet('tukarDari', null);
    }

    public function test_tukar_baris_dengan_diri_sendiri_diabaikan(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $a = $this->staffDi($unit);
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $a->id)
            ->set("polaGrid.{$a->id}.0", 'P')
            ->call('pilihTukar', $a->id)
            ->call('tukarBaris', $a->id)
            ->assertSet("polaGrid.{$a->id}.0", 'P')
            ->assertSet('tukarDari', null);
    }

    public function test_grid_template_urut_ikut_urutan_tambah_bukan_kunci_grid(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        // Ida dibuat lebih dulu → id lebih kecil; Taufik id lebih besar.
        $ida = $this->staffDi($unit);
        $ida->update(['nama_lengkap' => 'Ida Ayu']);
        $taufik = $this->staffDi($unit);
        $taufik->update(['nama_lengkap' => 'Taufik Hidayat']);
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        // Grid berurut kunci integer menaik (Ida id-kecil di depan) — meniru
        // reorder kunci objek oleh JS — tapi urutan tambah = Taufik dulu.
        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $taufik->id)   // ditambah lebih dulu
            ->call('tambahKaryawan', $ida->id)      // ditambah kemudian
            ->set('polaGrid', [$ida->id => [0 => 'P'], $taufik->id => [0 => 'P']])
            ->assertSeeInOrder(['Taufik Hidayat', 'Ida Ayu']);
    }

    public function test_simpan_menulis_baris_urut_urutan_tambah(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $ida = $this->staffDi($unit);
        $taufik = $this->staffDi($unit);
        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $pola->id)
            ->call('gantiTab', 'template')
            ->call('tambahKaryawan', $taufik->id)
            ->call('tambahKaryawan', $ida->id)
            ->set('tplJangkar', '2026-07-01')
            ->set('polaGrid', [$ida->id => [0 => 'P'], $taufik->id => [0 => 'P']])
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        // Taufik ditambah lebih dulu → barisnya harus dibuat lebih dulu (id lebih kecil).
        $minTaufik = \App\Models\PolaJadwal::where('template_id', $pola->id)->where('karyawan_id', $taufik->id)->min('id');
        $minIda = \App\Models\PolaJadwal::where('template_id', $pola->id)->where('karyawan_id', $ida->id)->min('id');
        $this->assertLessThan($minIda, $minTaufik);
    }

    public function test_tukar_antar_pola_menukar_anggota_siklus_tetap_di_polanya(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shiftP = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $shiftS = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'S']);
        $a = $this->staffDi($unit);
        $b = $this->staffDi($unit);

        $polaX = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola X', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $polaX->id, 'karyawan_id' => $a->id, 'posisi' => 0, 'shift_id' => $shiftP->id]);
        $polaY = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola Y', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $polaY->id, 'karyawan_id' => $b->id, 'posisi' => 0, 'shift_id' => $shiftS->id]);

        // Pola aktif Y; tukar A (dari Pola X) dengan B (anggota Pola Y).
        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $polaY->id)
            ->call('gantiTab', 'template')
            ->call('mulaiTukarLuar', $a->id)
            ->assertSet('tukarLuarId', $a->id)
            ->call('tukarAntarPola', $b->id)
            ->assertHasNoErrors()
            ->assertSet('tukarLuarId', null);

        // Siklus tetap di polanya: A masuk Pola Y pakai siklus lama Y (S),
        // B masuk Pola X pakai siklus lama X (P).
        $this->assertDatabaseHas('pola_jadwal', ['template_id' => $polaY->id, 'karyawan_id' => $a->id, 'posisi' => 0, 'shift_id' => $shiftS->id]);
        $this->assertDatabaseHas('pola_jadwal', ['template_id' => $polaX->id, 'karyawan_id' => $b->id, 'posisi' => 0, 'shift_id' => $shiftP->id]);
        // Tak ada A di X, tak ada B di Y.
        $this->assertDatabaseMissing('pola_jadwal', ['template_id' => $polaX->id, 'karyawan_id' => $a->id]);
        $this->assertDatabaseMissing('pola_jadwal', ['template_id' => $polaY->id, 'karyawan_id' => $b->id]);
        // Masing-masing tetap satu pola.
        $this->assertSame(1, \App\Models\PolaJadwal::where('karyawan_id', $a->id)->distinct('template_id')->count('template_id'));
        $this->assertSame(1, \App\Models\PolaJadwal::where('karyawan_id', $b->id)->distinct('template_id')->count('template_id'));
    }

    public function test_tukar_antar_pola_bisa_dibalik(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shiftP = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $shiftS = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'S']);
        $a = $this->staffDi($unit);
        $b = $this->staffDi($unit);

        $polaX = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola X', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $polaX->id, 'karyawan_id' => $a->id, 'posisi' => 0, 'shift_id' => $shiftP->id]);
        $polaY = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola Y', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $polaY->id, 'karyawan_id' => $b->id, 'posisi' => 0, 'shift_id' => $shiftS->id]);

        // Tukar A↔B (aktif Y), lalu balik lagi (aktif X) → kembali ke keadaan semula.
        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $polaY->id)->call('gantiTab', 'template')
            ->call('mulaiTukarLuar', $a->id)->call('tukarAntarPola', $b->id)
            // Sekarang B di X (siklus P), A di Y (siklus S). Balik: aktif X, tukar B(dari Y) dgn A(di X)...
            // A kini di Y, jadi dari sisi pola X, "luar" = A.
            ->call('gantiPola', $polaX->id)
            ->call('mulaiTukarLuar', $a->id)->call('tukarAntarPola', $b->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pola_jadwal', ['template_id' => $polaX->id, 'karyawan_id' => $a->id, 'posisi' => 0, 'shift_id' => $shiftP->id]);
        $this->assertDatabaseHas('pola_jadwal', ['template_id' => $polaY->id, 'karyawan_id' => $b->id, 'posisi' => 0, 'shift_id' => $shiftS->id]);
    }

    public function test_mulai_tukar_luar_diabaikan_untuk_karyawan_tanpa_pola_lain(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $lepas = $this->staffDi($unit);   // tidak di pola mana pun
        $polaY = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola Y', 'tanggal_jangkar' => '2026-07-01']);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->set('polaId', $polaY->id)
            ->call('gantiTab', 'template')
            ->call('mulaiTukarLuar', $lepas->id)
            ->assertSet('tukarLuarId', null);
    }

    public function test_grid_bulanan_mengelompokkan_per_pola_dan_tanpa_pola(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $jab = Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1]);
        $anggota = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id, 'nama_lengkap' => 'Anggota Pola']);
        Karyawan::factory()->create(['org_unit_id' => $unit->id, 'jabatan_id' => $jab->id, 'nama_lengkap' => 'Belum Berpola']);

        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola CS IGD', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $pola->id, 'karyawan_id' => $anggota->id, 'posisi' => 0, 'shift_id' => $shift->id]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'jadwal')
            ->assertSeeInOrder(['Pola CS IGD', 'Anggota Pola', 'Tanpa Pola', 'Belum Berpola']);
    }

    public function test_terapkan_pola_hanya_membentuk_jadwal_anggota_pola_itu(): void
    {
        $user = $this->koordinator();
        $unit = $user->karyawan->unitDipimpin()->first();
        $shift = \App\Models\Shift::factory()->create(['org_unit_id' => $unit->id, 'kode' => 'P']);
        $anggota = $this->staffDi($unit);
        $luar = $this->staffDi($unit);

        $pola = \App\Models\TemplateJadwal::create(['org_unit_id' => $unit->id, 'nama' => 'Pola A', 'tanggal_jangkar' => '2026-07-01']);
        \App\Models\PolaJadwal::create(['template_id' => $pola->id, 'karyawan_id' => $anggota->id, 'posisi' => 0, 'shift_id' => $shift->id]);

        Livewire::actingAs($user)->test(JadwalKelola::class)
            ->call('gantiTab', 'jadwal')->set('tahun', 2026)->set('bulan', 7)
            ->call('terapkanPola', $pola->id);

        $this->assertSame(31, \App\Models\Jadwal::where('karyawan_id', $anggota->id)->count());
        $this->assertSame(0, \App\Models\Jadwal::where('karyawan_id', $luar->id)->count());
    }
}
