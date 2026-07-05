<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Livewire\Cuti\CutiIndex;
use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\PengajuanCuti;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CutiIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    private function userEligible(): User
    {
        Carbon::setTestNow('2027-06-01');
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_menampilkan_saldo_dan_riwayat_sendiri(): void
    {
        $user = $this->userEligible();
        $kar = $user->karyawan;
        PengajuanCuti::factory()->for($kar)->jenis(KodeJenisCuti::CutiTahunan)->rentang('2027-07-01', '2027-07-02', 2)->create();
        // milik orang lain — tak boleh tampil
        PengajuanCuti::factory()->create();

        Livewire::actingAs($user)->test(CutiIndex::class)
            ->assertViewHas('saldo', fn ($s) => $s->jatah() === 12)
            ->assertViewHas('pengajuan', fn ($p) => $p->count() === 1);

        Carbon::setTestNow();
    }

    public function test_menampilkan_hari_libur_mendatang(): void
    {
        $user = $this->userEligible();
        HariLibur::create(['tanggal' => '2027-06-10', 'nama' => 'Cuti Bersama Uji']);
        HariLibur::create(['tanggal' => '2026-01-01', 'nama' => 'Libur Lampau']); // sudah lewat

        Livewire::actingAs($user)->test(CutiIndex::class)
            ->assertSee('Cuti Bersama Uji')
            ->assertDontSee('Libur Lampau');

        Carbon::setTestNow();
    }
}
