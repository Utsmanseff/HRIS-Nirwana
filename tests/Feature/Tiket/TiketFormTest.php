<?php

namespace Tests\Feature\Tiket;

use App\Enums\JenisTiket;
use App\Enums\Role;
use App\Enums\StatusAset;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Livewire\Tiket\TiketForm;
use App\Models\Aset;
use App\Models\Karyawan;
use App\Models\KategoriInventaris;
use App\Models\Tiket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TiketFormTest extends TestCase
{
    use RefreshDatabase;

    private function userTim(Role $role): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole($role->value);

        return $u;
    }

    public function test_tim_buat_tiket_baru_masuk_antrian(): void
    {
        Livewire::actingAs($this->userTim(Role::It))
            ->test(TiketForm::class)
            ->set('tim', TimTeknis::It->value)
            ->set('judul', 'Monitor rusak')
            ->set('deskripsi', 'Layar mati total.')
            ->set('prioritas', 'tinggi')
            ->set('statusLanjut', 'baru')
            ->call('simpan');

        $this->assertDatabaseHas('tiket', [
            'judul' => 'Monitor rusak',
            'status' => StatusTiket::Baru->value,
            'tim' => TimTeknis::It->value,
        ]);
    }

    public function test_taut_aset_set_tim_auto(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::Atem]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id, 'kode' => 'MED-9001', 'nama' => 'Ventilator A']);

        Livewire::actingAs($this->userTim(Role::Atem))
            ->test(TiketForm::class)
            ->call('pilihAset', $aset->id)
            ->assertSet('tim', TimTeknis::Atem->value)
            ->assertSet('asetLabel', 'Ventilator A (MED-9001)');
    }

    public function test_tim_langsung_selesai_isi_metrik_dan_aset_baik(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id, 'status' => StatusAset::Baik->value]);
        $u = $this->userTim(Role::It);

        Livewire::actingAs($u)->test(TiketForm::class)
            ->call('pilihAset', $aset->id)
            ->set('jenis', JenisTiket::Perbaikan->value)
            ->set('judul', 'Ganti keyboard')
            ->set('deskripsi', 'Sudah diganti saat itu juga.')
            ->set('statusLanjut', 'selesai')
            ->set('catatanPenyelesaian', 'Selesai via telepon.')
            ->call('simpan');

        $t = Tiket::firstWhere('judul', 'Ganti keyboard');
        $this->assertSame(StatusTiket::Selesai, $t->status);
        $this->assertNotNull($t->waktu_selesai);
        $this->assertSame($u->id, $t->penyelesai_id);
        // Aset langsung baik (tak nyangkut dalam_perbaikan).
        $this->assertSame(StatusAset::Baik, $aset->fresh()->status);
    }

    public function test_karyawan_self_service_pelapor_diri(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $kar = Karyawan::factory()->create();
        $u->update(['karyawan_id' => $kar->id]);
        $u->assignRole(Role::Karyawan->value);

        Livewire::actingAs($u)->test(TiketForm::class)
            ->assertSet('adalahTim', false)
            ->set('tim', TimTeknis::Sarana->value)
            ->set('judul', 'AC bocor')
            ->set('deskripsi', 'Menetes ke lantai.')
            ->set('prioritas', 'sedang')
            ->call('simpan');

        $this->assertDatabaseHas('tiket', [
            'judul' => 'AC bocor',
            'pelapor_id' => $kar->id,
            'status' => StatusTiket::Baru->value,
        ]);
    }
}
