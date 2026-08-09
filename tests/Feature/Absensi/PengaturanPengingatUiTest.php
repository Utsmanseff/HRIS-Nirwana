<?php

namespace Tests\Feature\Absensi;

use App\Livewire\Absensi\PengaturanAbsen;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PengaturanPengingatUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_simpan_pengingat_menyimpan_nilai(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(PengaturanAbsen::class)
            ->set('pengingatAktif', false)
            ->set('jedaMasukMenit', 20)
            ->set('jedaPulangMenit', 45)
            ->set('ambangNyangkutJam', 10)
            ->call('simpanPengingat')
            ->assertHasNoErrors();

        $p = PengaturanAbsensi::ambil()->fresh();
        $this->assertFalse($p->pengingat_aktif);
        $this->assertSame(20, $p->jeda_masuk_menit);
        $this->assertSame(45, $p->jeda_pulang_menit);
        $this->assertSame(10, $p->ambang_nyangkut_jam);
    }

    public function test_ambang_nyangkut_di_luar_batas_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(PengaturanAbsen::class)
            ->set('ambangNyangkutJam', 48)
            ->call('simpanPengingat')
            ->assertHasErrors(['ambangNyangkutJam']);
    }

    public function test_lokasi_tak_valid_tidak_memblokir_simpan_pengingat(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(PengaturanAbsen::class)
            ->set('officeLat', null)          // lokasi sengaja dirusak
            ->set('jedaMasukMenit', 25)
            ->call('simpanPengingat')
            ->assertHasNoErrors();

        $this->assertSame(25, PengaturanAbsensi::ambil()->fresh()->jeda_masuk_menit);
    }
}
