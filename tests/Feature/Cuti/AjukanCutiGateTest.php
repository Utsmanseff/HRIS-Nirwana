<?php

namespace Tests\Feature\Cuti;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AjukanCutiGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_karyawan_boleh_ajukan_cuti(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $this->assertTrue(Gate::forUser($user)->allows('ajukan-cuti'));
    }

    public function test_user_tanpa_karyawan_tidak_boleh(): void
    {
        $user = User::factory()->create(['karyawan_id' => null]);

        $this->assertFalse(Gate::forUser($user)->allows('ajukan-cuti'));
    }
}
