<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Enums\StatusPengajuanCuti;
use App\Livewire\Cuti\CutiDetail;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CutiDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    public function test_pemilik_bisa_lihat_detail_dan_batal_saat_diajukan(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $p = PengajuanCuti::factory()->for($kar)->jenis(KodeJenisCuti::CutiTahunan)
            ->status(StatusPengajuanCuti::Diajukan)->create();

        Livewire::actingAs($user)->test(CutiDetail::class, ['pengajuan' => $p])
            ->assertOk()
            ->call('batalkan')
            ->assertRedirect(route('cuti'));

        $this->assertSame(StatusPengajuanCuti::Dibatalkan, $p->refresh()->status);
    }

    public function test_bukan_pemilik_ditolak_403(): void
    {
        $p = PengajuanCuti::factory()->create();
        $lain = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        Livewire::actingAs($lain)->test(CutiDetail::class, ['pengajuan' => $p])
            ->assertForbidden();
    }

    public function test_tak_bisa_batal_saat_disetujui(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $p = PengajuanCuti::factory()->for($kar)->status(StatusPengajuanCuti::Disetujui)->create();

        Livewire::actingAs($user)->test(CutiDetail::class, ['pengajuan' => $p])
            ->call('batalkan')
            ->assertForbidden();

        $this->assertSame(StatusPengajuanCuti::Disetujui, $p->refresh()->status);
    }
}
