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
}
