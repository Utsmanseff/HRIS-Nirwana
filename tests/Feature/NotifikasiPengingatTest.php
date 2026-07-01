<?php

namespace Tests\Feature;

use App\Enums\SeverityPengingat;
use App\Models\Karyawan;
use App\Models\User;
use App\Notifications\KontrakAkanBerakhir;
use App\Notifications\SipAkanBerakhir;
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

    public function test_notif_sip_tersimpan_dengan_jenis_sip(): void
    {
        $hrd = User::factory()->create();
        $kar = Karyawan::factory()->create(['nama_lengkap' => 'Sari']);

        $hrd->notify(new SipAkanBerakhir($kar, SeverityPengingat::Terlewat, -5));

        $notif = $hrd->notifications()->first();
        $this->assertSame('sip', $notif->data['jenis']);
        $this->assertSame('terlewat', $notif->data['severity']);
    }
}
