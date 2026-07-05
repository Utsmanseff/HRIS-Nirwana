<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilPushDanNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_menampilkan_tombol_aktifkan_notifikasi(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        $res = $this->actingAs($user)->get('/profil');
        $res->assertOk();
        $res->assertSee('data-push-subscribe', false);
    }

    public function test_dashboard_sidebar_punya_link_profil(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::AdminSistem->value);

        $res = $this->actingAs($user)->get('/beranda');
        $res->assertOk();
        $res->assertSee(route('profil'), false); // href profil ada di shell
    }
}
