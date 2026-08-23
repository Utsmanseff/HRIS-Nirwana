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

    public function test_setiap_target_peta_menunjuk_bab_dan_bagian_yang_benar_benar_ada(): void
    {
        foreach (Panduan::rute() as $rute => $target) {
            if ($target === null) {
                continue; // penyekat, bukan target
            }

            $t = Panduan::untukRute($rute);

            $this->assertNotNull(Panduan::cari($t['slug']), "Rute {$rute} menunjuk bab {$t['slug']} yang tak ada di registry");
            $this->assertNotNull($t['bagian'], "Rute {$rute} wajib menyebut bagian (bab#bagian)");

            $html = $this->get('/panduan/'.$t['slug'])->assertOk()->getContent();

            $this->assertStringContainsString(
                'data-bagian="'.$t['bagian'].'"',
                $html,
                "Rute {$rute} menunjuk bagian '{$t['bagian']}' yang tak ada di bab {$t['slug']}",
            );
        }
    }
}
