<?php

namespace Tests\Feature\Disiplin;

use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Livewire\Disiplin\KelolaDisiplin;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Models\User;
use App\Notifications\SanksiDiterbitkan;
use App\Notifications\SanksiPerluPersetujuan;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaDisiplinTest extends TestCase
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

    public function test_hrd_bisa_akses_dan_lihat_semua_sanksi(): void
    {
        $user = $this->hrd();
        $sanksi = SanksiDisiplin::factory()->tingkat(TingkatSanksi::Sp1)->create([
            'status' => StatusSanksi::Diterbitkan,
        ]);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->assertOk()
            ->assertSee($sanksi->karyawan->nama_lengkap);
    }

    public function test_non_hrd_non_direktur_ditolak(): void
    {
        $this->seed(RoleSeeder::class);
        $kar = Karyawan::factory()->create();
        $user = User::factory()->create(['karyawan_id' => $kar->id]);
        $user->assignRole(Role::Karyawan->value);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)->assertForbidden();
    }

    public function test_filter_status_menyaring(): void
    {
        $user = $this->hrd();
        $terbit = SanksiDisiplin::factory()->create(['status' => StatusSanksi::Diterbitkan]);
        $tolak = SanksiDisiplin::factory()->create(['status' => StatusSanksi::Ditolak]);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->set('filterStatus', StatusSanksi::Diterbitkan->value)
            ->assertSee($terbit->karyawan->nama_lengkap)
            ->assertDontSee($tolak->karyawan->nama_lengkap);
    }

    public function test_pilih_karyawan_set_tingkat_saran(): void
    {
        $user = $this->hrd();
        $target = Karyawan::factory()->create();
        // Sanksi aktif Teguran1 → saran berikutnya Teguran2.
        SanksiDisiplin::factory()->diterbitkan(TingkatSanksi::Teguran1)
            ->create(['karyawan_id' => $target->id]);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->call('pilihKaryawan', $target->id)
            ->assertSet('karyawanId', $target->id)
            ->assertSet('tingkat', (string) TingkatSanksi::Teguran2->value);
    }

    public function test_cari_karyawan_org_wide(): void
    {
        $user = $this->hrd();
        Karyawan::factory()->create(['nama_lengkap' => 'Zulfikar Rahman']);

        Livewire::actingAs($user)->test(KelolaDisiplin::class)
            ->set('showForm', true)
            ->set('cariKaryawan', 'Zulfikar')
            ->assertSee('Zulfikar Rahman');
    }

    /** @return array{0: Karyawan, 1: User, 2: Karyawan} [direktur, userDirektur, target] */
    private function seedDirekturHrdTarget(): array
    {
        $this->seed(RoleSeeder::class);

        $direktorat = OrgUnit::factory()->create(['tipe' => 'direktur', 'parent_id' => null]);
        $unit = OrgUnit::factory()->create(['tipe' => 'unit', 'parent_id' => $direktorat->id]);

        $jabDir = Jabatan::factory()->create(['org_unit_id' => $direktorat->id, 'level' => 4]);
        $direktur = Karyawan::factory()->create(['org_unit_id' => $direktorat->id, 'jabatan_id' => $jabDir->id]);
        $uDir = User::factory()->create(['karyawan_id' => $direktur->id]);
        $uDir->assignRole(Role::Direktur->value);

        $target = Karyawan::factory()->create(['org_unit_id' => $unit->id]);

        return [$direktur, $uDir, $target];
    }

    public function test_direktur_buat_langsung_auto_terbit(): void
    {
        Notification::fake();
        Storage::fake('local');
        [$direktur, $uDir, $target] = $this->seedDirekturHrdTarget();
        User::factory()->create(['karyawan_id' => $target->id]);

        Livewire::actingAs($uDir)->test(KelolaDisiplin::class)
            ->call('pilihKaryawan', $target->id)
            ->set('tanggalKejadian', now()->subDay()->toDateString())
            ->set('uraian', 'Datang terlambat berulang.')
            ->set('nomorSurat', '01.777/DIR/RSUN/VII/2026')
            ->call('simpan');

        $sanksi = SanksiDisiplin::where('karyawan_id', $target->id)->firstOrFail();
        $this->assertSame(StatusSanksi::Diterbitkan, $sanksi->status);
        $this->assertNotNull($sanksi->tanggal_terbit);
        $this->assertNotEmpty($sanksi->surat_path);
        Notification::assertSentTo($target->fresh()->user, SanksiDiterbitkan::class);
    }

    public function test_hrd_buat_langsung_masuk_inbox_direktur(): void
    {
        Notification::fake();
        Storage::fake('local');
        [$direktur, $uDir, $target] = $this->seedDirekturHrdTarget();

        $hrdKar = Karyawan::factory()->create();
        $uHrd = User::factory()->create(['karyawan_id' => $hrdKar->id]);
        $uHrd->assignRole(Role::Hrd->value);

        Livewire::actingAs($uHrd)->test(KelolaDisiplin::class)
            ->call('pilihKaryawan', $target->id)
            ->set('tanggalKejadian', now()->subDay()->toDateString())
            ->set('uraian', 'Melanggar SOP.')
            ->set('nomorSurat', '01.778/HRD/RSUN/VII/2026')
            ->call('simpan');

        $sanksi = SanksiDisiplin::where('karyawan_id', $target->id)->firstOrFail();
        $this->assertSame(StatusSanksi::Diajukan, $sanksi->status);
        $this->assertSame(PeranApproval::Direktur, $sanksi->tahapAktif()->peran);
        $this->assertSame($direktur->id, $sanksi->tahapAktif()->approver_id);
        Notification::assertSentTo($direktur->user, SanksiPerluPersetujuan::class);
    }

    public function test_nomor_wajib_unik(): void
    {
        Storage::fake('local');
        [$direktur, $uDir, $target] = $this->seedDirekturHrdTarget();
        SanksiDisiplin::factory()->create(['nomor_surat' => '01.999/DIR/RSUN/VII/2026']);

        Livewire::actingAs($uDir)->test(KelolaDisiplin::class)
            ->call('pilihKaryawan', $target->id)
            ->set('tanggalKejadian', now()->subDay()->toDateString())
            ->set('uraian', 'x')
            ->set('nomorSurat', '01.999/DIR/RSUN/VII/2026')
            ->call('simpan')
            ->assertHasErrors(['nomorSurat']);
    }
}
