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
}
