<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SipKolomTest extends TestCase
{
    use RefreshDatabase;

    public function test_sip_nullable_default_kosong(): void
    {
        $kar = Karyawan::factory()->create();
        $this->assertNull($kar->sip_nomor);
        $this->assertNull($kar->sip_berlaku_akhir);
    }

    public function test_sip_tersimpan_dan_tanggal_di_cast(): void
    {
        $kar = Karyawan::factory()->create([
            'sip_nomor' => 'SIP/123/2026',
            'sip_berlaku_mulai' => '2026-01-01',
            'sip_berlaku_akhir' => '2031-01-01',
        ]);
        $this->assertSame('SIP/123/2026', $kar->fresh()->sip_nomor);
        $this->assertInstanceOf(Carbon::class, $kar->fresh()->sip_berlaku_akhir);
    }

    public function test_factory_state_with_sip(): void
    {
        $kar = Karyawan::factory()->withSip()->create();
        $this->assertNotNull($kar->sip_nomor);
        $this->assertNotNull($kar->sip_berlaku_akhir);
    }
}
