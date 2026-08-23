<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Karyawan;
use App\Models\User;
use App\Support\FragmenPanduan;
use App\Support\Panduan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class PanduanTanyaTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $user = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

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

    public function test_fragmen_mengembalikan_judul_dan_html_bagian(): void
    {
        $f = FragmenPanduan::ambil('cuti', 'ajukan');

        $this->assertNotNull($f);
        $this->assertSame('Mengajukan Cuti', $f['judul']);
        $this->assertStringContainsString('data-bagian="ajukan"', $f['html']);
        $this->assertStringContainsString('jenis cuti', $f['html']);
    }

    public function test_fragmen_membuang_baris_judul_dan_tautan_bagian_atas(): void
    {
        $f = FragmenPanduan::ambil('cuti', 'ajukan');

        // Judul sudah tampil di kepala sheet; menyisakannya bikin judul dobel.
        $this->assertStringNotContainsString('<h2', $f['html']);
        $this->assertStringNotContainsString('#atas', $f['html']);
    }

    public function test_fragmen_mendaftar_bagian_lain_di_bab_yang_sama(): void
    {
        $f = FragmenPanduan::ambil('cuti', 'ajukan');
        $id = array_column($f['lain'], 'id');

        $this->assertSame(['jatah', 'ajukan', 'status', 'rantai', 'menyetujui', 'surat'], $id);
        $this->assertSame('Memahami Jatah Cuti', $f['lain'][0]['judul']);
    }

    public function test_fragmen_null_untuk_bab_atau_bagian_asing(): void
    {
        $this->assertNull(FragmenPanduan::ambil('bab-asing', 'ajukan'));
        $this->assertNull(FragmenPanduan::ambil('cuti', 'bagian-asing'));
        $this->assertNull(FragmenPanduan::ambil('cuti', 'Ajukan Cuti'));
    }

    public function test_endpoint_bagian_terbuka_tanpa_login(): void
    {
        $this->getJson('/panduan/cuti/bagian/ajukan')
            ->assertOk()
            ->assertJsonPath('slug', 'cuti')
            ->assertJsonPath('id', 'ajukan')
            ->assertJsonPath('bab', 'Cuti')
            ->assertJsonPath('judul', 'Mengajukan Cuti')
            ->assertJsonStructure(['bab', 'slug', 'id', 'judul', 'html', 'lain' => [['id', 'judul']]]);
    }

    public function test_endpoint_bagian_404_untuk_bab_atau_bagian_asing(): void
    {
        $this->getJson('/panduan/bab-asing/bagian/ajukan')->assertNotFound();
        $this->getJson('/panduan/cuti/bagian/bagian-asing')->assertNotFound();
    }

    public function test_rute_bab_biasa_tidak_tertabrak_rute_bagian(): void
    {
        $this->get('/panduan/cuti')->assertOk()->assertSee('Mengajukan Cuti');
    }

    public function test_tombol_tanya_muncul_di_halaman_yang_dipetakan(): void
    {
        $this->actingAs($this->userSdm())->get('/beranda')
            ->assertOk()
            ->assertSee('Bantuan halaman ini')
            ->assertSee("tampil('dasar', 'beranda')", false);
    }

    public function test_tombol_tanya_tak_muncul_di_halaman_yang_tak_dipetakan(): void
    {
        $this->actingAs($this->userSdm())->get('/sdm/karyawan')
            ->assertOk()
            ->assertDontSee('Bantuan halaman ini');
    }

    public function test_sheet_tak_ikut_dirender_di_halaman_tanpa_tombol(): void
    {
        $this->actingAs($this->userSdm())->get('/sdm/karyawan')
            ->assertOk()
            ->assertDontSee('ptny-sheet');
    }

    public function test_bab_dasar_menjelaskan_tombol_tanya(): void
    {
        $this->get('/panduan/dasar')
            ->assertOk()
            ->assertSee('tanda tanya');
    }
}
