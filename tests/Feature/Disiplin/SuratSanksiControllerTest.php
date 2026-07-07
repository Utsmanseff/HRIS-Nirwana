<?php

namespace Tests\Feature\Disiplin;

use App\Enums\Role;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratSanksiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function sanksiTerbit(): SanksiDisiplin
    {
        Storage::fake('local');
        $kena = Karyawan::factory()->create();
        $pengusul = Karyawan::factory()->create();
        $sanksi = SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Sp1)->create([
            'karyawan_id' => $kena->id, 'pengusul_id' => $pengusul->id, 'surat_path' => 'sanksi/x/surat.pdf',
        ]);
        Storage::disk('local')->put('sanksi/x/surat.pdf', '%PDF-1.4 dummy');

        return $sanksi;
    }

    public function test_karyawan_kena_boleh_lihat(): void
    {
        $sanksi = $this->sanksiTerbit();
        $user = User::factory()->create(['karyawan_id' => $sanksi->karyawan_id]);

        $this->actingAs($user)->get(route('disiplin.surat', $sanksi))->assertOk();
    }

    public function test_hrd_boleh_lihat(): void
    {
        $sanksi = $this->sanksiTerbit();
        $hrd = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $hrd->assignRole(Role::Hrd->value);

        $this->actingAs($hrd)->get(route('disiplin.surat', $sanksi))->assertOk();
    }

    public function test_orang_lain_ditolak(): void
    {
        $sanksi = $this->sanksiTerbit();
        $lain = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);

        $this->actingAs($lain)->get(route('disiplin.surat', $sanksi))->assertForbidden();
    }
}
