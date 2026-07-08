<?php

namespace Tests\Feature\Tiket;

use App\Enums\Role;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Livewire\Tiket\TiketDetail;
use App\Models\Karyawan;
use App\Models\Tiket;
use App\Models\User;
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
}
