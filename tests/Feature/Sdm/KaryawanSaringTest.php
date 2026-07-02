<?php

namespace Tests\Feature\Sdm;

use App\Models\Karyawan;
use App\Models\OrgUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanSaringTest extends TestCase
{
    use RefreshDatabase;

    public function test_saring_cari_nama_atau_nip(): void
    {
        Karyawan::factory()->create(['nama_lengkap' => 'Budi Santoso', 'nip' => 'X-1']);
        Karyawan::factory()->create(['nama_lengkap' => 'Siti Aminah', 'nip' => 'X-2']);

        $this->assertSame(['Budi Santoso'], Karyawan::saring(['cari' => 'budi'])->pluck('nama_lengkap')->all());
        $this->assertSame(['Siti Aminah'], Karyawan::saring(['cari' => 'X-2'])->pluck('nama_lengkap')->all());
    }

    public function test_saring_unit_termasuk_turunan(): void
    {
        $bidang = OrgUnit::factory()->create();
        $divisi = OrgUnit::factory()->create(['parent_id' => $bidang->id]);
        $a = Karyawan::factory()->create(['org_unit_id' => $bidang->id]);
        $b = Karyawan::factory()->create(['org_unit_id' => $divisi->id]);
        Karyawan::factory()->create(); // unit lain

        $hasil = Karyawan::saring(['unit_id' => $bidang->id])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $hasil);
    }

    public function test_saring_status_default_tanpa_filter_mengembalikan_semua(): void
    {
        Karyawan::factory()->create(['status' => 'aktif']);
        Karyawan::factory()->create(['status' => 'nonaktif', 'alasan_nonaktif' => 'resign', 'tanggal_nonaktif' => now()]);

        $this->assertCount(2, Karyawan::saring([])->get());
        $this->assertCount(1, Karyawan::saring(['status' => 'nonaktif'])->get());
    }
}
