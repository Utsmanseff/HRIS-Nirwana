<?php

namespace Tests\Feature\Sdm;

use App\Models\Karyawan;

use App\Models\OrgUnit;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;

class ImportKaryawanSdmkTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtureCsvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureCsvPath = storage_path('app/import-sdmk/test_fixture.csv');
        $dir = dirname($this->fixtureCsvPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $header = ['nip', 'nik', 'nama_lengkap', 'unit_kerja', 'jabatan', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp', 'email', 'pendidikan_terakhir', 'tanggal_masuk', 'status'];
        $rows = [
            $header,
            ['Z 19851215 201202 01', '6372051512850002', 'dr. Deddi Reza Aldiano', 'Manajemen', 'Direktur', 'L', 'Banjarbaru', '1985-12-15', 'Jl. Permata Indah III', '081253533000', '', 'S1', '2012-01-02', 'aktif'],
            ['R 19940124 201610 01', '6303116401940003', 'Rizki Puspita, SKM.', 'Manajemen', 'SPI Pelayanan', 'P', 'Martapura', '1994-01-24', 'Jl. Ir. P.M. Noor', '085393394741', '', 'DIII', '2016-01-10', 'aktif'],
            ['TEMP-5', '6303051107850006', 'M. Suryana Rachman, S.Kep', 'Manajemen', 'SPI', 'L', 'Martapura', '1985-07-11', 'Komp. Mahkota', '087732682071', '', 'S1 Keperawatan', '', 'aktif'],
            ['19990609 202411 24', '6372060906990001', 'apt. Arief Agung Jatmiko', 'Instalasi Farmasi', 'Apoteker Pendamping', 'L', 'Banjarbaru', '1999-06-09', 'Jl. Dahlina Raya', '0895375846767', 'ariefagung0906@gmail.com', 'Apoteker Farmasi', '2024-11-01', 'aktif'],
            ['20011223 202502 06', '6303052312010007', 'Muhammad Ikhsan, S.Tr.Kes', 'Manajemen', 'Kabag Umum & Perlengkapan', 'L', 'Tabalong', '2001-12-23', 'Jl. Sekumpul', '08992553671', 'ikhsanmuhammad123@gmail.com', 'DIV Sanitasi', '2025-02-19', 'aktif'],
        ];

        $fp = fopen($this->fixtureCsvPath, 'w');
        foreach ($rows as $r) {
            fputcsv($fp, $r);
        }
        fclose($fp);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fixtureCsvPath)) {
            unlink($this->fixtureCsvPath);
        }
        parent::tearDown();
    }

    public function test_artisan_import_karyawan_sdmk_imports_csv_successfully(): void
    {
        $this->artisan('import:karyawan-sdmk', ['file' => $this->fixtureCsvPath])
            ->expectsOutputToContain('Import SDMK Selesai')
            ->assertExitCode(0);

        $this->assertSame(5, Karyawan::count());

        $surplus = Karyawan::where('nip', 'TEMP-5')->first();
        $this->assertNotNull($surplus);
        $this->assertSame('M. Suryana Rachman, S.Kep', $surplus->nama_lengkap);

        $deddi = Karyawan::where('nip', 'Z 19851215 201202 01')->first();
        $this->assertNotNull($deddi);
        $this->assertNotNull($deddi->org_unit_id);
        $this->assertSame($deddi->jabatan->org_unit_id, $deddi->org_unit_id);
    }

    public function test_artisan_import_karyawan_sdmk_is_idempotent(): void
    {
        $this->artisan('import:karyawan-sdmk', ['file' => $this->fixtureCsvPath])->assertExitCode(0);
        $this->assertSame(5, Karyawan::count());

        // Second run should update rather than duplicate
        $this->artisan('import:karyawan-sdmk', ['file' => $this->fixtureCsvPath])
            ->expectsOutputToContain('Berhasil di-update  : 5')
            ->assertExitCode(0);

        $this->assertSame(5, Karyawan::count());
    }
}
