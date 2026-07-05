<?php

namespace Tests\Feature\Cuti;

use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Models\Karyawan;
use App\Support\RantaiApproval;
use Database\Seeders\DemoSdmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_punya_hrd_dan_direktur(): void
    {
        $this->seed([RoleSeeder::class, DemoSdmSeeder::class]);

        $hrd = Karyawan::whereHas('user', fn ($q) => $q->role(Role::Hrd->value))->first();
        $dir = Karyawan::whereHas('user', fn ($q) => $q->role(Role::Direktur->value))->first();

        $this->assertNotNull($hrd, 'Seeder harus punya user role HRD');
        $this->assertNotNull($dir, 'Seeder harus punya user role Direktur');
    }

    public function test_rantai_staff_demo_berakhir_di_hrd(): void
    {
        $this->seed([RoleSeeder::class, DemoSdmSeeder::class]);

        // Ambil seorang staff (level 1) dari data demo.
        $staff = Karyawan::whereHas('jabatan', fn ($q) => $q->where('level', 1))->first();
        $this->assertNotNull($staff);

        $steps = RantaiApproval::susun($staff);

        $this->assertGreaterThanOrEqual(1, $steps->count());
        $this->assertSame(PeranApproval::Hrd, $steps->last()['peran']);
    }
}
