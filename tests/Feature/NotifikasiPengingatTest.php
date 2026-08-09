<?php

namespace Tests\Feature;

use App\Enums\KodeJenisIzin;
use App\Enums\SeverityPengingat;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\User;
use App\Notifications\IzinAkanBerakhir;
use App\Notifications\KontrakAkanBerakhir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiPengingatTest extends TestCase
{
    use RefreshDatabase;

    public function test_notif_kontrak_tersimpan_ke_database_dengan_payload(): void
    {
        $hrd = User::factory()->create();
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Budi']);

        $hrd->notify(new KontrakAkanBerakhir($kar, SeverityPengingat::AkanBerakhir, 20));

        $this->assertDatabaseCount('notifications', 1);
        $notif = $hrd->notifications()->first();
        $this->assertSame('kontrak', $notif->data['jenis']);
        $this->assertSame($kar->id, $notif->data['karyawan_id']);
        $this->assertSame('akan_berakhir', $notif->data['severity']);
        $this->assertStringContainsString('Budi', $notif->data['pesan']);
    }

    public function test_notif_izin_tersimpan_dengan_jenis_izin(): void
    {
        $hrd = User::factory()->create();
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Sari']);
        $izin = IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => JenisIzin::where('kode', KodeJenisIzin::Sip->value)->value('id'),
        ]);

        $hrd->notify(new IzinAkanBerakhir($izin, SeverityPengingat::Terlewat, -5));

        $notif = $hrd->notifications()->first();
        $this->assertSame('izin', $notif->data['jenis']);
        $this->assertSame('sip', $notif->data['kode_izin']);
        $this->assertSame('terlewat', $notif->data['severity']);
        $this->assertStringContainsString('Sari', $notif->data['pesan']);
    }
}
