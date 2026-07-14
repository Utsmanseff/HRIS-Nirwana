<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalSayaTest extends TestCase
{
    use RefreshDatabase;

    private function userKaryawan(): array
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        return [$user, $kar];
    }

    public function test_tampil_shift_bulan_ini(): void
    {
        [$user, $kar] = $this->userKaryawan();
        $shift = Shift::factory()->create(['nama' => 'Pagi', 'kode' => 'P']);
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'shift_id' => $shift->id, 'tanggal' => now()->startOfMonth()->addDays(2)]);

        $this->actingAs($user)->get('/absensi/jadwal-saya')
            ->assertOk()
            ->assertSee('Jadwal Saya')
            ->assertSee('Pagi');
    }

    public function test_bulan_lain_tak_bocor(): void
    {
        [$user, $kar] = $this->userKaryawan();
        $shift = Shift::factory()->create(['nama' => 'ShiftBulanLalu']);
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'shift_id' => $shift->id, 'tanggal' => now()->subMonth()->startOfMonth()->addDay()]);

        $this->actingAs($user)->get('/absensi/jadwal-saya')
            ->assertOk()
            ->assertDontSee('ShiftBulanLalu');
    }

    public function test_geser_bulan(): void
    {
        [$user, $kar] = $this->userKaryawan();
        $shift = Shift::factory()->create(['nama' => 'ShiftDepan']);
        Jadwal::factory()->create(['karyawan_id' => $kar->id, 'shift_id' => $shift->id, 'tanggal' => now()->addMonth()->startOfMonth()->addDay()]);

        Livewire::actingAs($user)->test(\App\Livewire\Absensi\JadwalSaya::class)
            ->assertDontSee('ShiftDepan')
            ->call('geser', 1)
            ->assertSee('ShiftDepan');
    }

    public function test_jadwal_orang_lain_tak_tampil(): void
    {
        [$user, $kar] = $this->userKaryawan();
        $lain = Karyawan::factory()->create();
        $shift = Shift::factory()->create(['nama' => 'ShiftOrangLain']);
        Jadwal::factory()->create(['karyawan_id' => $lain->id, 'shift_id' => $shift->id, 'tanggal' => now()]);

        $this->actingAs($user)->get('/absensi/jadwal-saya')
            ->assertOk()
            ->assertDontSee('ShiftOrangLain');
    }

    public function test_kosong_tampil_empty_state(): void
    {
        [$user] = $this->userKaryawan();

        $this->actingAs($user)->get('/absensi/jadwal-saya')
            ->assertOk()
            ->assertSee('Belum ada jadwal');
    }

    public function test_menu_sanksi_saya_dan_jadwal_saya_muncul_untuk_karyawan(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        [$user] = $this->userKaryawan();
        $user->assignRole(\App\Enums\Role::Karyawan->value);

        $ids = collect(\App\Support\NavMenu::untuk($user))->pluck('id');

        $this->assertTrue($ids->contains('sanksi-saya'), 'menu sanksi-saya harus muncul');
        $this->assertTrue($ids->contains('jadwal-saya'), 'menu jadwal-saya harus muncul');
    }
}
