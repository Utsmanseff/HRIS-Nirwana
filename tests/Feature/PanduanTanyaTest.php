<?php

namespace Tests\Feature;

use App\Support\Panduan;
use Tests\TestCase;

class PanduanTanyaTest extends TestCase
{
    public function test_rute_persis_mendapat_targetnya(): void
    {
        $this->assertSame(
            ['slug' => 'cuti', 'bagian' => 'ajukan'],
            Panduan::untukRute('cuti.ajukan'),
        );
    }

    public function test_sub_rute_mewarisi_target_induknya(): void
    {
        // 'tiket.detail' tak dipetakan sendiri; prefix 'tiket' yang menanganinya.
        $this->assertSame(
            ['slug' => 'tiket', 'bagian' => 'memantau'],
            Panduan::untukRute('tiket.detail'),
        );
    }

    public function test_halaman_admin_disekat_meski_induknya_dipetakan(): void
    {
        // Tanpa penyekat null, ketiganya akan mewarisi tombol induknya.
        $this->assertNull(Panduan::untukRute('cuti.kelola'));
        $this->assertNull(Panduan::untukRute('cuti.laporan.saldo'));
        $this->assertNull(Panduan::untukRute('absensi.pengaturan'));
    }

    public function test_prefix_terpanjang_menang(): void
    {
        // 'tiket.buat' harus menang atas 'tiket'.
        $this->assertSame(
            ['slug' => 'tiket', 'bagian' => 'melapor'],
            Panduan::untukRute('tiket.buat'),
        );
    }

    public function test_rute_tak_dipetakan_mengembalikan_null(): void
    {
        $this->assertNull(Panduan::untukRute('sdm.karyawan'));
        $this->assertNull(Panduan::untukRute('sistem.pengguna'));
        $this->assertNull(Panduan::untukRute(null));
    }
}
