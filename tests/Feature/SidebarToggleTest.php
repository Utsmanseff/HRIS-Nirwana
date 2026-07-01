<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_punya_tombol_toggle_dan_state_alpine(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::AdminSistem->value);

        $res = $this->actingAs($user)->get('/dashboard');
        $res->assertOk();
        $res->assertSee('data-sb-toggle', false);          // tombol toggle sidebar
        $res->assertSee("var KEY = 'nirwana-sidebar'", false); // init state no-flash (head script, pre-paint)
        $res->assertSee('root.dataset.sidebar', false);    // data attribute dibaca CSS sebelum Alpine boot
    }
}
