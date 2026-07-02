<?php

namespace Tests\Feature\Sdm;

use App\Enums\Permission;
use App\Livewire\Sdm\KaryawanDetail;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class KaryawanNonaktifTest extends TestCase
{
    use RefreshDatabase;

    private function userSdm(): User
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::KelolaSdm->value, 'web'));

        return $user;
    }

    public function test_nonaktifkan_karyawan(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('formNonaktif')
            ->set('alasanNonaktif', 'resign')
            ->set('tanggalNonaktif', '2026-07-31')
            ->call('nonaktifkan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('karyawan', ['id' => $kar->id, 'status' => 'nonaktif', 'alasan_nonaktif' => 'resign', 'tanggal_nonaktif' => '2026-07-31 00:00:00']);
    }

    public function test_nonaktif_wajib_alasan(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'aktif']);

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('formNonaktif')
            ->set('alasanNonaktif', '')
            ->call('nonaktifkan')
            ->assertHasErrors(['alasanNonaktif']);
    }

    public function test_aktifkan_lagi(): void
    {
        $kar = Karyawan::factory()->create(['status' => 'nonaktif', 'alasan_nonaktif' => 'resign', 'tanggal_nonaktif' => '2026-01-01']);

        Livewire::actingAs($this->userSdm())->test(KaryawanDetail::class, ['karyawan' => $kar])
            ->call('aktifkanLagi')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('karyawan', ['id' => $kar->id, 'status' => 'aktif', 'alasan_nonaktif' => null, 'tanggal_nonaktif' => null]);
    }
}
