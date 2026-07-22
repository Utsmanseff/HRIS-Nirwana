<?php

namespace Tests\Feature\Cuti;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrasiPenugasanPenggantiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_lama_hilang_tabel_baru_ada(): void
    {
        $this->assertFalse(Schema::hasTable('pengganti_cuti'));
        $this->assertTrue(Schema::hasTable('penugasan_pengganti'));
    }

    public function test_kolom_baru_lengkap(): void
    {
        $this->assertTrue(Schema::hasColumns('penugasan_pengganti', [
            'tipe', 'pengajuan_cuti_id', 'karyawan_digantikan_id', 'karyawan_id',
            'tanggal_mulai', 'tanggal_selesai', 'status', 'dibuat_oleh',
        ]));
    }

    public function test_penanda_jadwal_berganti_nama(): void
    {
        $this->assertFalse(Schema::hasColumn('jadwal', 'pengganti_cuti_id'));
        $this->assertTrue(Schema::hasColumn('jadwal', 'pengganti_id'));
    }
}
