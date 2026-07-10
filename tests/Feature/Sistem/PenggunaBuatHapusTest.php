<?php

namespace Tests\Feature\Sistem;

use App\Enums\Role;
use App\Enums\StatusKaryawan;
use App\Livewire\Sistem\PenggunaKelola;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PenggunaBuatHapusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create(['karyawan_id' => Karyawan::factory()->create()->id]);
        $this->admin->assignRole(Role::AdminSistem->value);
        $this->actingAs($this->admin);
    }

    public function test_buat_akun_menautkan_karyawan_dan_role_karyawan(): void
    {
        $kar = Karyawan::factory()->create([
            'status' => StatusKaryawan::Aktif->value,
            'email' => 'budi@contoh.test',
        ]);

        $c = Livewire::test(PenggunaKelola::class)
            ->call('buatAkun', $kar->id)
            ->assertHasNoErrors();

        $user = User::where('karyawan_id', $kar->id)->first();
        $this->assertNotNull($user);
        $this->assertSame($kar->nama_lengkap, $user->name);
        $this->assertSame('budi@contoh.test', $user->email);
        $this->assertTrue($user->hasRole(Role::Karyawan->value));
        // Sandi sementara ditampilkan sekali.
        $this->assertNotNull($c->get('sandiBaru'));
    }

    public function test_buat_akun_sintesis_email_bila_karyawan_tanpa_email(): void
    {
        $kar = Karyawan::factory()->create([
            'status' => StatusKaryawan::Aktif->value,
            'email' => null,
            'nip' => 'PWT-0007',
        ]);

        Livewire::test(PenggunaKelola::class)->call('buatAkun', $kar->id);

        $user = User::where('karyawan_id', $kar->id)->first();
        $this->assertSame('pwt-0007@nirwana.local', $user->email);
    }

    public function test_buat_akun_tolak_karyawan_sudah_tertaut(): void
    {
        $kar = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif->value]);
        User::factory()->create(['karyawan_id' => $kar->id]);

        Livewire::test(PenggunaKelola::class)
            ->call('buatAkun', $kar->id)
            ->assertHasErrors('buat');

        $this->assertSame(1, User::where('karyawan_id', $kar->id)->count());
    }

    public function test_hapus_akun_menghapus_baris_user(): void
    {
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);

        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $user->id)
            ->call('hapus')
            ->assertHasNoErrors();

        $this->assertNull(User::find($user->id));
        // Karyawan bebas → bisa dibuat akun ulang.
        $this->assertNull(User::where('karyawan_id', $kar->id)->first());
    }

    public function test_tidak_bisa_hapus_akun_sendiri(): void
    {
        Livewire::test(PenggunaKelola::class)
            ->call('bukaKelola', $this->admin->id)
            ->call('hapus')
            ->assertHasErrors('kelola');

        $this->assertNotNull(User::find($this->admin->id));
    }
}
