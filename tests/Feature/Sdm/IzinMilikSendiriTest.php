<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Enums\SeverityPengingat;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\User;
use App\Notifications\IzinAkanBerakhir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IzinMilikSendiriTest extends TestCase
{
    use RefreshDatabase;

    private function izinSip(Karyawan $kar, string $akhir = '2030-01-01'): IzinKaryawan
    {
        return IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => JenisIzin::where('kode', KodeJenisIzin::Sip->value)->value('id'),
            'nomor' => 'SIP/321/2026',
            'berlaku_akhir' => $akhir,
        ]);
    }

    public function test_url_notif_untuk_hrd_ke_detail_karyawan(): void
    {
        $kar = Karyawan::factory()->create();
        $izin = $this->izinSip($kar);
        $hrd = User::factory()->create(); // bukan karyawan ybs

        $data = (new IzinAkanBerakhir($izin, SeverityPengingat::AkanBerakhir, 20))->toArray($hrd);

        $this->assertSame('/sdm/karyawan/'.$kar->id, $data['url']);
    }

    public function test_url_notif_untuk_karyawan_ybs_ke_profil(): void
    {
        $kar = Karyawan::factory()->create();
        $izin = $this->izinSip($kar);
        $userKar = User::factory()->create(['karyawan_id' => $kar->id]);

        $data = (new IzinAkanBerakhir($izin, SeverityPengingat::AkanBerakhir, 20))->toArray($userKar);

        $this->assertSame('/profil', $data['url']);
    }

    public function test_profil_menampilkan_perizinan_sendiri(): void
    {
        $kar = Karyawan::factory()->create();
        $this->izinSip($kar, now()->addDays(20)->toDateString());
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/profil')
            ->assertOk()
            ->assertSee('Perizinan Saya')
            ->assertSee('SIP/321/2026')
            ->assertSee('H-20');
    }

    public function test_profil_tak_bocorkan_izin_orang_lain(): void
    {
        $kar = Karyawan::factory()->create();
        $lain = Karyawan::factory()->create();
        $this->izinSip($lain);
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->actingAs($user)->get('/profil')
            ->assertOk()
            ->assertDontSee('SIP/321/2026');
    }
}
