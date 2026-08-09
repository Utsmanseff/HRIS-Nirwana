<?php

namespace Tests\Feature;

use App\Enums\KodeJenisIzin;
use App\Enums\Permission;
use App\Livewire\Beranda;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class BerandaPerizinanTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_perizinan_memuat_izin_hampir_habis(): void
    {
        $aktor = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $aktor->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));
        $this->actingAs($user);

        $kar = Karyawan::factory()->create(['status' => 'aktif', 'nama_lengkap' => 'Siti Nakes']);
        IzinKaryawan::factory()->create([
            'karyawan_id' => $kar->id,
            'jenis_izin_id' => JenisIzin::where('kode', KodeJenisIzin::Sip->value)->value('id'),
            'berlaku_akhir' => now()->addDays(10),
        ]);

        Livewire::test(Beranda::class)
            ->assertViewHas('pengingatIzin', fn ($p) => $p->count() === 1)
            ->assertSee('Siti Nakes');
    }
}
