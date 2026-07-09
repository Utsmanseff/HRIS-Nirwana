<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\PengaturanAbsensi;
use App\Models\Shift;
use Database\Seeders\DemoSdmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAbsensiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_membuat_shift_absensi_dan_pengaturan(): void
    {
        $this->seed([RoleSeeder::class, DemoSdmSeeder::class, \Database\Seeders\DemoAbsensiSeeder::class]);

        $this->assertGreaterThan(0, Shift::count());
        $this->assertGreaterThan(0, Absensi::count());
        $this->assertSame(1, PengaturanAbsensi::count());
    }
}
