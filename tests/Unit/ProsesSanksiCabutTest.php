<?php

namespace Tests\Unit;

use App\Enums\StatusSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Notifications\SanksiDicabut;
use App\Support\ProsesSanksi;
use App\Support\ProsesSanksiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProsesSanksiCabutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cabut_sanksi_diterbitkan(): void
    {
        Notification::fake();
        $target = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $target->id]);
        $hrd = User::factory()->create();
        $sanksi = SanksiDisiplin::factory()->diterbitkan()->create(['karyawan_id' => $target->id]);

        ProsesSanksi::cabut($sanksi, $hrd, 'Kesalahan administrasi.');

        $sanksi->refresh();
        $this->assertSame(StatusSanksi::Dicabut, $sanksi->status);
        $this->assertSame($hrd->id, $sanksi->dicabut_oleh);
        $this->assertSame('Kesalahan administrasi.', $sanksi->alasan_cabut);
        $this->assertNotNull($sanksi->dicabut_pada);
        Notification::assertSentTo($target->fresh()->user, SanksiDicabut::class);
    }

    public function test_cabut_selain_diterbitkan_ditolak(): void
    {
        $hrd = User::factory()->create();
        $sanksi = SanksiDisiplin::factory()->create(['status' => StatusSanksi::Diajukan]);

        $this->expectException(ProsesSanksiException::class);
        ProsesSanksi::cabut($sanksi, $hrd, 'apa saja');
    }
}
