<?php

namespace Tests\Feature;

use App\Support\Panduan;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PanduanTest extends TestCase
{
    public function test_registry_punya_slug_unik_dan_field_lengkap(): void
    {
        $bab = Panduan::semua();

        $this->assertNotEmpty($bab);
        $this->assertSame(
            count($bab),
            count(array_unique(array_column($bab, 'slug'))),
            'Slug bab wajib unik',
        );

        foreach ($bab as $b) {
            foreach (['slug', 'judul', 'ringkas', 'ikon', 'peran', 'grup'] as $field) {
                $this->assertArrayHasKey($field, $b, "Bab {$b['slug']} kehilangan field {$field}");
            }
            $this->assertIsArray($b['peran']);
            $this->assertNotEmpty($b['peran'], "Bab {$b['slug']} wajib punya minimal satu peran");
        }
    }

    public function test_cari_mengembalikan_bab_atau_null(): void
    {
        $pertama = Panduan::semua()[0];

        $this->assertSame($pertama, Panduan::cari($pertama['slug']));
        $this->assertNull(Panduan::cari('slug-yang-tidak-ada'));
    }

    public function test_daftar_isi_bisa_dibuka_tanpa_login(): void
    {
        $this->get('/panduan')
            ->assertOk()
            ->assertSee('Panduan Aplikasi');
    }

    public function test_setiap_bab_bisa_dibuka_tanpa_login(): void
    {
        foreach (Panduan::semua() as $bab) {
            $this->get('/panduan/'.$bab['slug'])
                ->assertOk()
                ->assertSee($bab['judul']);
        }
    }

    public function test_slug_asing_menghasilkan_404(): void
    {
        $this->get('/panduan/tidak-ada')->assertNotFound();
    }

    public function test_setiap_bab_punya_file_view(): void
    {
        foreach (Panduan::semua() as $bab) {
            $this->assertTrue(
                View::exists('panduan.'.$bab['slug']),
                "Bab {$bab['slug']} terdaftar di registry tapi view panduan/{$bab['slug']}.blade.php tidak ada",
            );
        }
    }

    public function test_sampul_menampilkan_logo_dan_menautkan_daftar_isi(): void
    {
        $this->get('/panduan')
            ->assertOk()
            ->assertSee('img/icon.png', false)
            ->assertSee('id="daftar-isi"', false)
            ->assertSee('href="#daftar-isi"', false);
    }

    public function test_berkas_logo_sampul_ada_di_disk(): void
    {
        $this->assertFileExists(public_path('img/icon.png'));
    }

    public function test_setiap_bab_menyediakan_jalan_balik_ke_daftar_isi(): void
    {
        foreach (Panduan::semua() as $bab) {
            $this->get('/panduan/'.$bab['slug'])
                ->assertOk()
                ->assertSee(route('panduan'), false);
        }
    }

    public function test_tautan_panduan_muncul_di_halaman_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('Panduan Aplikasi');
    }

    public function test_item_panduan_terlihat_semua_peran(): void
    {
        $item = collect(\App\Support\NavMenu::semua())->firstWhere('id', 'panduan');

        $this->assertNotNull($item, 'Item panduan belum terdaftar di NavMenu');
        $this->assertNull($item['can'], 'Panduan harus terlihat semua peran');
    }

    public function test_bab_pertama_dan_terakhir_tak_punya_tetangga_di_ujungnya(): void
    {
        $semua = Panduan::semua();
        $terakhir = $semua[count($semua) - 1];

        $this->assertNull(Panduan::tetangga($terakhir['slug'])['sesudah']);
        $this->assertNull(Panduan::tetangga($semua[0]['slug'])['sebelum']);
    }
}
