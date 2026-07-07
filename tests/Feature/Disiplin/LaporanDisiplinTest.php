<?php

namespace Tests\Feature\Disiplin;

use App\Enums\Role;
use App\Enums\StatusSanksi;
use App\Livewire\Disiplin\LaporanDisiplin;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanDisiplinTest extends TestCase
{
    use RefreshDatabase;

    private function hrd(): User
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Hrd->value);

        return $user;
    }

    public function test_hrd_lihat_tabel_dan_strip(): void
    {
        $user = $this->hrd();
        $kar = Karyawan::factory()->create();
        SanksiDisiplin::factory()->create([
            'karyawan_id' => $kar->id,
            'status' => StatusSanksi::Diterbitkan,
            'tanggal_kejadian' => now()->toDateString(),
        ]);

        Livewire::actingAs($user)->test(LaporanDisiplin::class)
            ->assertOk()
            ->assertSee($kar->nama_lengkap)
            ->assertSee('Diterbitkan');
    }

    public function test_non_hrd_ditolak(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Karyawan->value);

        Livewire::actingAs($user)->test(LaporanDisiplin::class)->assertForbidden();
    }

    public function test_ekspor_xlsx(): void
    {
        $user = $this->hrd();
        Karyawan::factory()->has(SanksiDisiplin::factory()->count(1), 'sanksiDisiplin')->create();

        $this->actingAs($user)
            ->get(route('disiplin.laporan.sanksi', ['format' => 'xlsx']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_ekspor_pdf(): void
    {
        $user = $this->hrd();
        Karyawan::factory()->has(SanksiDisiplin::factory()->count(1), 'sanksiDisiplin')->create();

        $res = $this->actingAs($user)->get(route('disiplin.laporan.sanksi', ['format' => 'pdf']));
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));
    }
}
