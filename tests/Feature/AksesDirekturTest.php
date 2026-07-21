<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\User;
use App\Support\NavMenu;
use Database\Seeders\DemoSdmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesDirekturTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, DemoSdmSeeder::class]);
    }

    private function user(string $nip): User
    {
        $kar = Karyawan::where('nip', $nip)->firstOrFail();

        return $kar->user()->firstOrFail();
    }

    /** @return list<string> */
    private function idMenu(User $u): array
    {
        return array_column(NavMenu::untuk($u), 'id');
    }

    public function test_direktur_ditolak_di_route_yang_dicabut(): void
    {
        $dir = $this->user('DIR-0001');

        foreach (['/cuti', '/cuti/ajukan', '/disiplin/saya', '/absensi/jadwal-saya'] as $url) {
            $this->actingAs($dir)->get($url)->assertForbidden();
        }
    }

    public function test_direktur_tetap_bisa_akses_yang_dipertahankan(): void
    {
        $dir = $this->user('DIR-0001');

        foreach (['/absensi', '/cuti/persetujuan', '/disiplin/kelola', '/beranda'] as $url) {
            $this->actingAs($dir)->get($url)->assertOk();
        }
    }

    public function test_menu_direktur_tanpa_item_yang_dicabut(): void
    {
        $ids = $this->idMenu($this->user('DIR-0001'));

        foreach (['absensi-jadwal', 'sanksi-saya', 'disiplin', 'jadwal-saya', 'cuti'] as $id) {
            $this->assertNotContains($id, $ids, "menu $id seharusnya tak tampil untuk Direktur");
        }

        $this->assertContains('absensi', $ids);
        $this->assertContains('persetujuan', $ids);
        $this->assertContains('disiplin-kelola', $ids);
    }

    public function test_koordinator_tidak_kehilangan_menu(): void
    {
        $ids = $this->idMenu($this->user('KOR-0001'));

        foreach (['cuti', 'absensi', 'jadwal-saya', 'sanksi-saya', 'absensi-jadwal', 'disiplin'] as $id) {
            $this->assertContains($id, $ids, "menu $id seharusnya tetap ada untuk Koordinator");
        }
    }

    public function test_hrd_tidak_kehilangan_menu(): void
    {
        $ids = $this->idMenu($this->user('HRD-0001'));

        foreach (['cuti', 'absensi', 'jadwal-saya', 'sanksi-saya', 'kelola-cuti', 'laporan-cuti'] as $id) {
            $this->assertContains($id, $ids, "menu $id seharusnya tetap ada untuk HRD");
        }
    }

    public function test_koordinator_masih_bisa_buka_route_self_service(): void
    {
        $koor = $this->user('KOR-0001');

        foreach (['/cuti', '/cuti/ajukan', '/disiplin/saya', '/absensi/jadwal-saya'] as $url) {
            $this->actingAs($koor)->get($url)->assertOk();
        }
    }

    public function test_beranda_direktur_tanpa_kartu_jatah_cuti(): void
    {
        $this->actingAs($this->user('DIR-0001'))->get('/beranda')
            ->assertOk()
            ->assertDontSee('hari tersisa');
    }

    public function test_beranda_koordinator_tetap_punya_kartu_jatah(): void
    {
        $this->actingAs($this->user('KOR-0001'))->get('/beranda')
            ->assertOk()
            ->assertSee('JATAH');
    }

    /**
     * Feed riwayat menggabungkan cuti/tiket/sanksi/absensi — untuk Direktur sumbernya
     * hampir semua kosong, pastikan halamannya tetap render, bukan error.
     */
    public function test_riwayat_direktur_tetap_render(): void
    {
        $this->actingAs($this->user('DIR-0001'))->get('/riwayat')->assertOk();
    }

    /** Staf biasa tak punya akun di DemoSdmSeeder, jadi dibuatkan di sini. */
    public function test_karyawan_biasa_tidak_kehilangan_akses(): void
    {
        $staf = Karyawan::whereHas('jabatan', fn ($q) => $q->where('level', 1))->firstOrFail();
        $user = User::factory()->create(['karyawan_id' => $staf->id]);
        $user->assignRole(\App\Enums\Role::Karyawan->value);

        $ids = array_column(NavMenu::untuk($user), 'id');
        foreach (['cuti', 'absensi', 'jadwal-saya', 'sanksi-saya'] as $id) {
            $this->assertContains($id, $ids, "menu $id seharusnya tetap ada untuk karyawan biasa");
        }

        foreach (['/cuti', '/disiplin/saya', '/absensi/jadwal-saya'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }
}
