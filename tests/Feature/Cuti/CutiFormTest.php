<?php

namespace Tests\Feature\Cuti;

use App\Livewire\Cuti\CutiForm;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CutiFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
        // Preview alur di form memanggil RantaiApproval::susun (butuh definisi role).
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function userEligible(): User
    {
        Carbon::setTestNow('2027-06-01');
        $kar = Karyawan::factory()->create();
        Kontrak::factory()->for($kar)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);

        return User::factory()->create(['karyawan_id' => $kar->id]);
    }

    public function test_form_render_dengan_opsi_jenis_eligible(): void
    {
        Livewire::actingAs($this->userEligible())->test(CutiForm::class)
            ->assertOk()
            ->assertViewHas('jenisOptions', fn ($opts) => $opts->pluck('kode')->map(fn ($k) => $k->value)->contains('cuti_tahunan'));
        Carbon::setTestNow();
    }

    public function test_submit_valid_membuat_pengajuan_dan_rantai(): void
    {
        $user = $this->userEligible();
        // sediakan HRD agar rantai punya approver final
        $hrd = Karyawan::factory()->create();
        User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(\App\Enums\Role::Hrd->value);

        $jenisId = \App\Models\JenisCuti::where('kode', 'cuti_tahunan')->value('id');

        Livewire::actingAs($user)->test(CutiForm::class)
            ->set('jenisCutiId', (string) $jenisId)
            ->set('tanggalMulai', '2027-07-10')
            ->set('tanggalSelesai', '2027-07-12')
            ->set('jumlahHari', 3)
            ->call('simpan')
            ->assertRedirect(route('cuti'));

        $p = \App\Models\PengajuanCuti::where('karyawan_id', $user->karyawan_id)->first();
        $this->assertNotNull($p);
        $this->assertSame(\App\Enums\StatusPengajuanCuti::Diajukan, $p->status);
        $this->assertGreaterThanOrEqual(1, $p->approval()->count());
        Carbon::setTestNow();
    }

    public function test_submit_cuti_tahunan_lebih_dari_enam_ditolak(): void
    {
        $user = $this->userEligible();
        $jenisId = \App\Models\JenisCuti::where('kode', 'cuti_tahunan')->value('id');

        Livewire::actingAs($user)->test(CutiForm::class)
            ->set('jenisCutiId', (string) $jenisId)
            ->set('tanggalMulai', '2027-07-01')
            ->set('tanggalSelesai', '2027-07-10')
            ->set('jumlahHari', 7)
            ->call('simpan')
            ->assertHasErrors('jumlahHari');

        $this->assertSame(0, \App\Models\PengajuanCuti::count());
        Carbon::setTestNow();
    }

    public function test_submit_izin_biasa_tanpa_lampiran_ditolak(): void
    {
        $user = $this->userEligible();
        $jenisId = \App\Models\JenisCuti::where('kode', 'izin_biasa')->value('id');

        Livewire::actingAs($user)->test(CutiForm::class)
            ->set('jenisCutiId', (string) $jenisId)
            ->set('tanggalMulai', '2027-07-01')
            ->set('tanggalSelesai', '2027-07-02')
            ->set('jumlahHari', 2)
            ->call('simpan')
            ->assertHasErrors('lampiran');

        Carbon::setTestNow();
    }

    public function test_submit_cuti_sakit_backdate_dengan_lampiran_tersimpan(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $user = $this->userEligible();
        $jenisId = \App\Models\JenisCuti::where('kode', 'cuti_sakit')->value('id');

        Livewire::actingAs($user)->test(CutiForm::class)
            ->set('jenisCutiId', (string) $jenisId)
            ->set('tanggalMulai', '2027-05-20') // backdate diizinkan utk sakit
            ->set('tanggalSelesai', '2027-05-21')
            ->set('jumlahHari', 2)
            ->set('lampiran', \Illuminate\Http\UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'))
            ->call('simpan')
            ->assertRedirect(route('cuti'));

        $p = \App\Models\PengajuanCuti::first();
        $this->assertNotNull($p->lampiran_path);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($p->lampiran_path);
        Carbon::setTestNow();
    }

    public function test_submit_kirim_notif_ke_approver_pertama(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Carbon::setTestNow('2027-06-01');

        // Koordinator (approver pertama) + staff pemohon eligible (kontrak Pkwt).
        $unit = \App\Models\OrgUnit::factory()->create();
        $koor = \App\Models\Karyawan::factory()->pimpinanUnit($unit)->create();
        $userKoor = \App\Models\User::factory()->create(['karyawan_id' => $koor->id]);
        $pemohon = \App\Models\Karyawan::factory()->staffUnit($unit)->create();
        \App\Models\Kontrak::factory()->for($pemohon)->create([
            'jenis' => \App\Enums\JenisKontrak::Pkwt->value,
            'tanggal_mulai' => '2026-03-01', 'tanggal_akhir' => '2028-03-01',
        ]);
        $userPemohon = \App\Models\User::factory()->create(['karyawan_id' => $pemohon->id]);
        // HRD final approver agar rantai lengkap.
        $hrd = \App\Models\Karyawan::factory()->create();
        \App\Models\User::factory()->create(['karyawan_id' => $hrd->id])->assignRole(\App\Enums\Role::Hrd->value);

        $jenis = \App\Models\JenisCuti::where('kode', \App\Enums\KodeJenisCuti::CutiTahunan->value)->first();

        \Livewire\Livewire::actingAs($userPemohon)->test(\App\Livewire\Cuti\CutiForm::class)
            ->set('jenisCutiId', (string) $jenis->id)
            ->set('tanggalMulai', '2027-07-10')
            ->set('tanggalSelesai', '2027-07-11')
            ->set('jumlahHari', 2)
            ->set('alasan', 'acara keluarga')
            ->call('simpan')
            ->assertHasNoErrors();

        // Approver pertama = koordinator (kepala unit) dapat notif.
        \Illuminate\Support\Facades\Notification::assertSentTo($userKoor, \App\Notifications\CutiPerluPersetujuan::class);
        \Illuminate\Support\Carbon::setTestNow();
    }
}
