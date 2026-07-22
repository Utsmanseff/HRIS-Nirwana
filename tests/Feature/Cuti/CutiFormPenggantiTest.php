<?php

namespace Tests\Feature\Cuti;

use App\Enums\KodeJenisCuti;
use App\Livewire\Cuti\CutiForm;
use App\Models\Jadwal;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PenggantiCuti;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CutiFormPenggantiTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $pemohon;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
        Notification::fake();

        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create([
            'tanggal_masuk' => now()->subYears(3),
        ]);
        // Kontrak tetap sejak 3 tahun lalu → eligible + jatah cuti tahunan terisi.
        \App\Models\Kontrak::factory()->for($this->pemohon)->create([
            'jenis' => \App\Enums\JenisKontrak::Tetap->value,
            'tanggal_mulai' => now()->subYears(3)->toDateString(),
            'tanggal_akhir' => null,
        ]);
        $this->user = User::factory()->create(['karyawan_id' => $this->pemohon->id]);
    }

    private function idJenis(KodeJenisCuti $kode): int
    {
        return JenisCuti::where('kode', $kode->value)->value('id');
    }

    public function test_flag_unit_menentukan_picker_tampil(): void
    {
        Livewire::actingAs($this->user)->test(CutiForm::class)
            ->assertViewHas('pakaiPengganti', true);

        $this->unit->update(['pakai_pengganti' => false]);

        Livewire::actingAs($this->user)->test(CutiForm::class)
            ->assertViewHas('pakaiPengganti', false);
    }

    public function test_cari_pengganti_lintas_unit_kecuali_diri_sendiri(): void
    {
        $lain = OrgUnit::factory()->create();
        Karyawan::factory()->staffUnit($lain)->create(['nama_lengkap' => 'Siti Aminah']);
        $this->pemohon->update(['nama_lengkap' => 'Siti Pemohon']);

        Livewire::actingAs($this->user)->test(CutiForm::class)
            ->set('cariPengganti', 'Siti')
            ->assertViewHas('hasilCariPengganti', fn ($h) => $h->pluck('nama_lengkap')->all() === ['Siti Aminah']);
    }

    public function test_pilih_pengganti_bentrok_menampilkan_error(): void
    {
        $pagi = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'M', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $b = Karyawan::factory()->staffUnit($this->unit)->create();
        $mulai = now()->addDays(5)->toDateString();
        Jadwal::factory()->create(['karyawan_id' => $this->pemohon->id, 'tanggal' => $mulai, 'shift_id' => $pagi->id]);
        Jadwal::factory()->create(['karyawan_id' => $b->id, 'tanggal' => $mulai, 'shift_id' => $pagi->id]);

        Livewire::actingAs($this->user)->test(CutiForm::class)
            ->set('jenisCutiId', (string) $this->idJenis(KodeJenisCuti::CutiTahunan))
            ->set('tanggalMulai', $mulai)
            ->set('tanggalSelesai', $mulai)
            ->set('jumlahHari', 1)
            ->set('alasan', 'Keperluan keluarga')
            ->call('pilihPengganti', $b->id)
            ->assertHasErrors('penggantiId')
            ->assertSet('penggantiId', null);

        $this->assertSame(0, PenggantiCuti::count());
    }

    public function test_simpan_dengan_pengganti_membuat_rencana(): void
    {
        $b = Karyawan::factory()->staffUnit($this->unit)->create();
        $mulai = now()->addDays(5)->toDateString();

        Livewire::actingAs($this->user)->test(CutiForm::class)
            ->set('jenisCutiId', (string) $this->idJenis(KodeJenisCuti::CutiTahunan))
            ->set('tanggalMulai', $mulai)
            ->set('tanggalSelesai', $mulai)
            ->set('jumlahHari', 1)
            ->set('alasan', 'Keperluan keluarga')
            ->call('pilihPengganti', $b->id)
            ->assertHasNoErrors()
            ->call('simpan');

        $this->assertSame(1, PenggantiCuti::where('karyawan_id', $b->id)->count());
    }

    public function test_simpan_tanpa_pengganti_tetap_boleh(): void
    {
        $mulai = now()->addDays(5)->toDateString();

        Livewire::actingAs($this->user)->test(CutiForm::class)
            ->set('jenisCutiId', (string) $this->idJenis(KodeJenisCuti::CutiTahunan))
            ->set('tanggalMulai', $mulai)
            ->set('tanggalSelesai', $mulai)
            ->set('jumlahHari', 1)
            ->set('alasan', 'Keperluan keluarga')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame(0, PenggantiCuti::count());
    }
}
