<?php

namespace Tests\Unit\Notifications;

use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Notifications\CutiDibatalkan;
use App\Notifications\CutiDisetujui;
use App\Notifications\CutiDitolak;
use App\Notifications\CutiPerluPersetujuan;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CutiNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
    }

    public function test_isi_notif_perlu_persetujuan(): void
    {
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Budi']);
        $p = PengajuanCuti::factory()->for($kar)->create();
        $user = User::factory()->create();

        $arr = (new CutiPerluPersetujuan($p))->toArray($user);
        $this->assertSame('cuti', $arr['jenis']);
        $this->assertSame('/cuti/persetujuan', $arr['url']);
        $this->assertStringContainsString('Budi', $arr['pesan']);
    }

    public function test_url_notif_pemohon_ke_detail(): void
    {
        $p = PengajuanCuti::factory()->create();
        $user = User::factory()->create();

        $this->assertSame('/cuti/'.$p->id, (new CutiDisetujui($p))->toArray($user)['url']);
        $this->assertSame('/cuti/'.$p->id, (new CutiDitolak($p, 'tak lengkap'))->toArray($user)['url']);
        $this->assertStringContainsString('tak lengkap', (new CutiDitolak($p, 'tak lengkap'))->toArray($user)['pesan']);
        $this->assertStringContainsString('sibuk', (new CutiDibatalkan($p, 'sibuk'))->toArray($user)['pesan']);
    }
}
