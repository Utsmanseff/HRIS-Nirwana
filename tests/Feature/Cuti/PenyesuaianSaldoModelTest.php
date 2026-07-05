<?php

namespace Tests\Feature\Cuti;

use App\Models\Karyawan;
use App\Models\PenyesuaianSaldo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenyesuaianSaldoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_penyesuaian_dengan_delta_dan_relasi(): void
    {
        $kar = Karyawan::factory()->create();
        $hrd = User::factory()->create();

        $p = PenyesuaianSaldo::create([
            'karyawan_id' => $kar->id,
            'periode_mulai' => '2026-03-01',
            'delta' => -2,
            'alasan' => 'Koreksi kelebihan pakai',
            'dibuat_oleh' => $hrd->id,
        ]);

        $this->assertSame(-2, (int) $p->delta);
        $this->assertTrue($p->karyawan->is($kar));
        $this->assertSame('2026-03-01', $p->periode_mulai->format('Y-m-d'));
    }
}
