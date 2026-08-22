<?php

namespace Tests\Feature;

use App\Support\Panduan;
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
}
