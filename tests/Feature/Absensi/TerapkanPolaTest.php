<?php

namespace Tests\Feature\Absensi;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use App\Support\TerapkanPola;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerapkanPolaTest extends TestCase
{
    use RefreshDatabase;

    private function siklusPL(OrgUnit $unit, Karyawan $kar, Shift $shift): TemplateJadwal
    {
        // Siklus 2 hari: [Pagi, Libur], jangkar 2026-07-01.
        $tpl = TemplateJadwal::create(['org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01']);
        PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $kar->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $kar->id, 'posisi' => 1, 'shift_id' => null]);

        return $tpl;
    }

    public function test_generate_mengisi_jadwal_sesuai_siklus_mod(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->for($unit, 'orgUnit')->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $this->siklusPL($unit, $kar, $shift);

        $jumlah = TerapkanPola::generate($unit, 2026, 7); // Juli 31 hari

        // pos genap = Pagi (hari 1,3,5,...,31 = 16 hari), pos ganjil = Libur (15 hari, tak dibuat)
        $this->assertSame(16, $jumlah);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-01 00:00:00', 'shift_id' => $shift->id]);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-03 00:00:00', 'shift_id' => $shift->id]);
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-02']);
        $this->assertSame(16, Jadwal::where('karyawan_id', $kar->id)->count());
    }

    public function test_generate_menimpa_jadwal_lama_bulan_itu(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->for($unit, 'orgUnit')->create();
        $lain = Shift::factory()->for($unit, 'orgUnit')->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $this->siklusPL($unit, $kar, $shift);

        // Jadwal manual lama: 1 Juli pakai shift lain, 2 Juli ada (harusnya libur → dihapus)
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-01', 'shift_id' => $lain->id]);
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-02', 'shift_id' => $lain->id]);

        TerapkanPola::generate($unit, 2026, 7);

        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-01 00:00:00', 'shift_id' => $shift->id]);
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-02']);
    }

    public function test_bulan_lain_tidak_terpengaruh_tukar_shift(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->for($unit, 'orgUnit')->create();
        $lain = Shift::factory()->for($unit, 'orgUnit')->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $this->siklusPL($unit, $kar, $shift);

        TerapkanPola::generate($unit, 2026, 7);
        // Tukar shift di Juli (edit jadwal aktual, template tak berubah)
        Jadwal::where('karyawan_id', $kar->id)->where('tanggal', '2026-07-01')->update(['shift_id' => $lain->id]);

        // Generate Agustus: tetap ikut template (deterministik), tak terpengaruh tukar Juli
        TerapkanPola::generate($unit, 2026, 8);
        // 1 Agustus: offset dari 1 Juli = 31 hari, 31 mod 2 = 1 → Libur
        $this->assertDatabaseMissing('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-08-01']);
        // 2 Agustus: offset 32 mod 2 = 0 → Pagi
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-08-02 00:00:00', 'shift_id' => $shift->id]);
    }

    public function test_generate_tanpa_template_nol(): void
    {
        $unit = OrgUnit::factory()->create();
        $this->assertSame(0, TerapkanPola::generate($unit, 2026, 7));
    }

    public function test_mode_mingguan_ikut_nama_hari(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->for($unit, 'orgUnit')->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);

        // Mode mingguan: Senin (posisi 0) = Pagi, sisanya libur.
        $tpl = TemplateJadwal::create([
            'org_unit_id' => $unit->id, 'tanggal_jangkar' => '2026-07-01',
            'mode' => \App\Enums\ModeTemplate::Mingguan->value,
        ]);
        PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $kar->id, 'posisi' => 0, 'shift_id' => $shift->id]);
        for ($p = 1; $p < 7; $p++) {
            PolaJadwal::create(['template_id' => $tpl->id, 'karyawan_id' => $kar->id, 'posisi' => $p, 'shift_id' => null]);
        }

        TerapkanPola::generate($unit, 2026, 7);

        $rows = \App\Models\Jadwal::where('karyawan_id', $kar->id)->get();
        $this->assertTrue($rows->every(fn ($j) => $j->tanggal->dayOfWeekIso === 1 && (int) $j->shift_id === $shift->id));

        $jumlahSenin = collect(range(1, 31))
            ->filter(fn ($d) => \Illuminate\Support\Carbon::create(2026, 7, $d)->dayOfWeekIso === 1)->count();
        $this->assertSame($jumlahSenin, $rows->count());
    }

    public function test_auto_non_destruktif_tak_menimpa_manual(): void
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->for($unit, 'orgUnit')->create();
        $lain = Shift::factory()->for($unit, 'orgUnit')->create();
        $kar = Karyawan::factory()->create(['org_unit_id' => $unit->id]);
        $this->siklusPL($unit, $kar, $shift); // rotasi [P, L], jangkar 1 Jul

        // Manual: 1 Jul (pos0=P) diisi $lain; 2 Jul (pos1=libur) diisi $lain.
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-01', 'shift_id' => $lain->id]);
        Jadwal::create(['karyawan_id' => $kar->id, 'tanggal' => '2026-07-02', 'shift_id' => $lain->id]);

        TerapkanPola::generate($unit, 2026, 7, null, false); // timpa=false (auto)

        // 1 Jul TETAP $lain (tak ditimpa), 2 Jul (libur) TETAP $lain (tak dihapus)
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-01 00:00:00', 'shift_id' => $lain->id]);
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-02 00:00:00', 'shift_id' => $lain->id]);
        // 3 Jul (pos0=P) belum ada → dibuat
        $this->assertDatabaseHas('jadwal', ['karyawan_id' => $kar->id, 'tanggal' => '2026-07-03 00:00:00', 'shift_id' => $shift->id]);
    }
}
