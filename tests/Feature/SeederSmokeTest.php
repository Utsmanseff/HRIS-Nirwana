<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_default_buat_admin(): void
    {
        $this->seed();
        $admin = User::whereHas('karyawan', fn ($q) => $q->where('nip', 'ADMIN-0001'))->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole(Role::AdminSistem->value));
        $this->assertGreaterThan(0, Karyawan::count());
    }

    public function test_demo_seeder_bangun_hierarki_dengan_atasan_derived(): void
    {
        $this->seed([\Database\Seeders\RoleSeeder::class, \Database\Seeders\DemoSdmSeeder::class]);

        // Ada tepat 1 node direktur.
        $this->assertSame(1, \App\Models\OrgUnit::where('tipe', 'direktur')->count());

        // Ambil seorang staff; atasannya harus koordinator unitnya (level >=2).
        $staff = \App\Models\Karyawan::whereHas('jabatan', fn ($q) => $q->where('level', 1))->first();
        $this->assertNotNull($staff);
        $atasan = $staff->atasanDerived();
        $this->assertNotNull($atasan);
        $this->assertGreaterThanOrEqual(2, $atasan->jabatan->level->value);
    }
}
