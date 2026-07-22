<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Livewire\Pengganti\PenggantiKelola;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\PengajuanCuti;
use App\Models\PenugasanPengganti;
use App\Models\Shift;
use App\Models\User;
use App\Support\ProsesPengganti;
use Database\Seeders\JenisCutiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PenggantiKelolaTest extends TestCase
{
    use RefreshDatabase;

    protected OrgUnit $unit;

    protected Karyawan $koor;

    protected Karyawan $pemohon;

    protected Karyawan $b;

    protected Karyawan $c;

    protected User $userKoor;

    protected User $userC;

    protected PengajuanCuti $cuti;

    protected string $t1;

    protected string $t2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisCutiSeeder::class);
        Notification::fake();

        $this->t1 = now()->addDays(2)->toDateString();
        $this->t2 = now()->addDays(3)->toDateString();

        $this->unit = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $this->koor = Karyawan::factory()->pimpinanUnit($this->unit)->create();
        $this->pemohon = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->b = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->c = Karyawan::factory()->staffUnit($this->unit)->create();
        $this->userKoor = User::factory()->create(['karyawan_id' => $this->koor->id]);
        $this->userKoor->assignRole('Karyawan');
        $this->userC = User::factory()->create(['karyawan_id' => $this->c->id]);
        $this->userC->assignRole('Karyawan');

        $pagi = Shift::factory()->create([
            'org_unit_id' => $this->unit->id, 'kode' => 'M', 'jam_mulai' => '07:00:00', 'jam_selesai' => '14:00:00',
        ]);
        $this->cuti = PengajuanCuti::factory()->rentang($this->t1, $this->t2, 2)
            ->status(StatusPengajuanCuti::Disetujui)
            ->create(['karyawan_id' => $this->pemohon->id]);
        foreach ([$this->t1, $this->t2] as $t) {
            Jadwal::factory()->create([
                'karyawan_id' => $this->pemohon->id, 'tanggal' => $t, 'shift_id' => $pagi->id,
            ]);
        }
        ProsesPengganti::tetapkan($this->cuti, $this->b, $this->userKoor);
    }

    public function test_route_butuh_gate_ajukan_cuti(): void
    {
        $tanpaKaryawan = User::factory()->create(['karyawan_id' => null]);

        // Akun belum tertaut karyawan dicegat middleware klaim (redirect), sebelum gate.
        $this->actingAs($tanpaKaryawan)->get('/pengganti')->assertRedirect();
        $this->actingAs($this->userC)->get('/pengganti')->assertOk();
    }

    public function test_rute_baru_terdaftar_dan_rute_lama_hilang(): void
    {
        $this->assertSame('/pengganti', route('pengganti', absolute: false));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('cuti.pengganti'));
    }

    public function test_nav_memuat_item_pengganti_jadwal(): void
    {
        $ids = array_column(\App\Support\NavMenu::semua(), 'id');

        $this->assertContains('pengganti', $ids);
        $this->assertNotContains('cuti-pengganti', $ids);
    }

    public function test_daftar_hanya_cuti_berjalan_di_unit_saya(): void
    {
        $lain = OrgUnit::factory()->create(['pakai_pengganti' => true]);
        $luar = Karyawan::factory()->staffUnit($lain)->create();
        PengajuanCuti::factory()->rentang($this->t1, $this->t2, 2)
            ->status(StatusPengajuanCuti::Disetujui)->create(['karyawan_id' => $luar->id]);

        Livewire::actingAs($this->userC)->test(PenggantiKelola::class)
            ->assertViewHas('daftar', fn ($d) => $d->pluck('id')->all() === [$this->cuti->id]);
    }

    public function test_cuti_yang_sudah_lewat_tidak_muncul(): void
    {
        $lewat = PengajuanCuti::factory()
            ->rentang(now()->subDays(9)->toDateString(), now()->subDays(7)->toDateString(), 3)
            ->status(StatusPengajuanCuti::Disetujui)
            ->create(['karyawan_id' => $this->pemohon->id]);

        Livewire::actingAs($this->userC)->test(PenggantiKelola::class)
            ->assertViewHas('daftar', fn ($d) => ! $d->pluck('id')->contains($lewat->id));
    }

    public function test_rekan_bisa_ajukan_diri(): void
    {
        Livewire::actingAs($this->userC)->test(PenggantiKelola::class)
            ->call('mulaiAjukan', $this->cuti->id)
            ->set('tanggalAksi', $this->t2)
            ->call('kirimAjukanDiri');

        $this->assertSame(1, PenugasanPengganti::usulan()->where('karyawan_id', $this->c->id)->count());
    }

    public function test_koordinator_bisa_alihkan(): void
    {
        Livewire::actingAs($this->userKoor)->test(PenggantiKelola::class)
            ->call('mulaiAlih', $this->cuti->id)
            ->set('tanggalAksi', $this->t2)
            ->call('pilihAlih', $this->c->id);

        $this->assertSame(1, Jadwal::where('karyawan_id', $this->c->id)->salinanPengganti()->count());
        $this->assertSame(1, Jadwal::where('karyawan_id', $this->b->id)->salinanPengganti()->count());
    }

    public function test_koordinator_acc_dan_tolak_usulan(): void
    {
        $usulan = ProsesPengganti::ajukanDiri(
            $this->cuti->fresh(), $this->c, Carbon::parse($this->t2), $this->userC,
        );

        Livewire::actingAs($this->userKoor)->test(PenggantiKelola::class)
            ->call('tolak', $usulan->id);

        $this->assertSame(0, PenugasanPengganti::usulan()->count());

        $usulan2 = ProsesPengganti::ajukanDiri(
            $this->cuti->fresh(), $this->c, Carbon::parse($this->t2), $this->userC,
        );

        Livewire::actingAs($this->userKoor)->test(PenggantiKelola::class)
            ->call('acc', $usulan2->id);

        $this->assertSame(0, PenugasanPengganti::usulan()->count());
        $this->assertSame(1, Jadwal::where('karyawan_id', $this->c->id)->salinanPengganti()->count());
    }

    public function test_bukan_koordinator_gagal_alihkan(): void
    {
        Livewire::actingAs($this->userC)->test(PenggantiKelola::class)
            ->call('mulaiAlih', $this->cuti->id)
            ->set('tanggalAksi', $this->t2)
            ->call('pilihAlih', $this->b->id)
            ->assertHasErrors('tanggalAksi');

        $this->assertSame(0, Jadwal::where('karyawan_id', $this->c->id)->salinanPengganti()->count());
    }

    public function test_alasan_cuti_tidak_dibocorkan(): void
    {
        $this->cuti->update(['alasan' => 'RAHASIAKELUARGA']);

        Livewire::actingAs($this->userC)->test(PenggantiKelola::class)
            ->assertDontSee('RAHASIAKELUARGA');
    }
}
