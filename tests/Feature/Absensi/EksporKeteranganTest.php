<?php

namespace Tests\Feature\Absensi;

use App\Exports\AbsensiExport;
use App\Exports\AbsensiUnitSheet;
use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PenugasanPengganti;
use App\Models\Shift;
use App\Support\LabelPengganti;
use App\Support\RekapAbsensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EksporKeteranganTest extends TestCase
{
    use RefreshDatabase;

    private array $filter = ['dari' => '2026-08-01', 'sampai' => '2026-08-30'];

    public function test_kolom_keterangan_ada_di_heading_excel(): void
    {
        $this->skenario();

        $export = new AbsensiExport($this->filter);

        // headings() = 6 baris kop + baris nama kolom → ambil baris terakhir.
        $this->assertContains('Keterangan', last($export->headings()));
    }

    public function test_baris_excel_memuat_label_pengganti(): void
    {
        $this->skenario();

        $export = new AbsensiExport($this->filter);
        $baris = $export->collection()->first();

        $this->assertSame('Mengisi jadwal kosong — Siti', last($export->map($baris)));
    }

    public function test_dinas_biasa_kolom_keterangan_kosong_di_excel(): void
    {
        [$unit, $shift, $pengganti] = $this->skenario();
        Absensi::factory()->create([
            'karyawan_id' => $pengganti->id, 'tanggal_kerja' => '2026-08-10', 'shift_id' => $shift->id,
        ]);

        $export = new AbsensiExport($this->filter);
        $biasa = $export->collection()
            ->first(fn (Absensi $a) => $a->tanggal_kerja->toDateString() === '2026-08-10');

        $this->assertSame('', last($export->map($biasa)));
    }

    public function test_sheet_per_unit_juga_memuat_keterangan(): void
    {
        [$unit] = $this->skenario();

        $grup = RekapAbsensi::perUnit($this->filter)->first();
        $sheet = new AbsensiUnitSheet($grup['unit'], $grup['baris']);

        $this->assertContains('Keterangan', last($sheet->headings()));
        $this->assertSame('Mengisi jadwal kosong — Siti', last($sheet->map($sheet->collection()->first())));
    }

    public function test_pdf_tunggal_memuat_teks_keterangan(): void
    {
        $this->skenario();
        $baris = RekapAbsensi::ambil($this->filter);

        $html = view('laporan.pdf.absensi', [
            'baris' => $baris,
            'keterangan' => LabelPengganti::petaAbsensi($baris),
            'keteranganFilter' => 'uji',
        ])->render();

        $this->assertStringContainsString('Mengisi jadwal kosong — Siti', $html);
    }

    public function test_pdf_per_unit_memuat_teks_keterangan(): void
    {
        $this->skenario();
        $grup = RekapAbsensi::perUnit($this->filter);

        $html = view('laporan.pdf.absensi-per-unit', [
            'grup' => $grup,
            'keterangan' => LabelPengganti::petaAbsensi($grup->flatMap(fn (array $g) => $g['baris'])),
            'keteranganFilter' => 'uji',
        ])->render();

        $this->assertStringContainsString('Mengisi jadwal kosong — Siti', $html);
    }

    /** @return array{0:OrgUnit,1:Shift,2:Karyawan} */
    private function skenario(): array
    {
        $unit = OrgUnit::factory()->create();
        $shift = Shift::factory()->create(['org_unit_id' => $unit->id]);
        $siti = Karyawan::factory()->create(['org_unit_id' => $unit->id, 'nama_lengkap' => 'Siti']);
        $pengganti = Karyawan::factory()->create(['org_unit_id' => $unit->id]);

        $rencana = PenugasanPengganti::factory()->lowongan()->create([
            'karyawan_digantikan_id' => $siti->id, 'karyawan_id' => $pengganti->id,
        ]);
        Jadwal::create([
            'karyawan_id' => $pengganti->id, 'tanggal' => '2026-08-04',
            'shift_id' => $shift->id, 'pengganti_id' => $rencana->id,
        ]);
        Absensi::factory()->create([
            'karyawan_id' => $pengganti->id, 'tanggal_kerja' => '2026-08-04', 'shift_id' => $shift->id,
        ]);

        return [$unit, $shift, $pengganti];
    }
}
