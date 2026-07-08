<?php

namespace Tests\Feature\Tiket;

use App\Enums\JenisTiket;
use App\Enums\Role;
use App\Enums\StatusAset;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Livewire\Tiket\TiketDetail;
use App\Models\Aset;
use App\Models\Karyawan;
use App\Models\KategoriInventaris;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TiketDetailTest extends TestCase
{
    use RefreshDatabase;

    private function userTim(Role $role): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole($role->value);

        return $u;
    }

    public function test_tim_mulai_lalu_selesai(): void
    {
        $t = Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Baru)->create(['waktu_respon' => null]);
        $u = $this->userTim(Role::It);

        Livewire::actingAs($u)->test(TiketDetail::class, ['tiket' => $t])
            ->call('mulai')
            ->assertOk();
        $this->assertSame(StatusTiket::Diproses, $t->fresh()->status);

        Livewire::actingAs($u)->test(TiketDetail::class, ['tiket' => $t->fresh()])
            ->set('catatanSelesai', 'Sudah diperbaiki.')
            ->call('selesaikan');
        $this->assertSame(StatusTiket::Selesai, $t->fresh()->status);
        $this->assertSame($u->id, $t->fresh()->penyelesai_id);
    }

    public function test_tim_lain_ditolak(): void
    {
        $t = Tiket::factory()->tim(TimTeknis::Atem)->create();
        $u = $this->userTim(Role::It);

        Livewire::actingAs($u)->test(TiketDetail::class, ['tiket' => $t])->assertForbidden();
    }

    public function test_pelapor_boleh_lihat_tiket_sendiri(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $kar = Karyawan::factory()->create();
        $u->update(['karyawan_id' => $kar->id]);
        $u->assignRole(Role::Karyawan->value);
        $t = Tiket::factory()->tim(TimTeknis::It)->create(['pelapor_id' => $kar->id]);

        Livewire::actingAs($u)->test(TiketDetail::class, ['tiket' => $t])->assertOk();
    }

    public function test_tim_taut_aset_ke_tiket_tanpa_aset(): void
    {
        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id, 'status' => StatusAset::Baik->value]);
        $t = Tiket::factory()->tim(TimTeknis::It)->jenis(JenisTiket::Perbaikan)
            ->status(StatusTiket::Diproses)->create(['inventaris_id' => null, 'waktu_respon' => now()]);
        $u = $this->userTim(Role::It);

        Livewire::actingAs($u)->test(TiketDetail::class, ['tiket' => $t])
            ->call('tautAset', $aset->id);

        $this->assertSame($aset->id, $t->fresh()->inventaris_id);
        // Perbaikan + aktif → aset dalam_perbaikan.
        $this->assertSame(StatusAset::DalamPerbaikan, $aset->fresh()->status);
    }

    public function test_tim_koreksi_waktu_respon(): void
    {
        $t = Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Diproses)->create([
            'waktu_lapor' => Carbon::parse('2026-06-01 08:00'),
            'waktu_respon' => Carbon::parse('2026-06-01 09:00'),
        ]);
        $u = $this->userTim(Role::It);

        Livewire::actingAs($u)->test(TiketDetail::class, ['tiket' => $t])
            ->call('mulaiEditRespon')
            ->set('waktuResponInput', '2026-06-01T08:10')
            ->call('simpanWaktuRespon')
            ->assertSet('editRespon', false);

        $this->assertSame('2026-06-01 08:10', $t->fresh()->waktu_respon->format('Y-m-d H:i'));
    }

    public function test_koreksi_respon_tolak_sebelum_lapor(): void
    {
        $t = Tiket::factory()->tim(TimTeknis::It)->status(StatusTiket::Diproses)->create([
            'waktu_lapor' => Carbon::parse('2026-06-01 08:00'),
            'waktu_respon' => Carbon::parse('2026-06-01 09:00'),
        ]);
        $u = $this->userTim(Role::It);

        Livewire::actingAs($u)->test(TiketDetail::class, ['tiket' => $t])
            ->call('mulaiEditRespon')
            ->set('waktuResponInput', '2026-06-01T07:00')
            ->call('simpanWaktuRespon')
            ->assertHasErrors('waktuResponInput');

        $this->assertSame('2026-06-01 09:00', $t->fresh()->waktu_respon->format('Y-m-d H:i'));
    }
}
