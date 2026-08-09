<?php

namespace Tests\Feature\Sdm;

use App\Enums\KodeJenisIzin;
use App\Models\JenisIzin;
use Database\Seeders\JenisIzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JenisIzinSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_empat_jenis(): void
    {
        $this->seed(JenisIzinSeeder::class);

        $this->assertSame(4, JenisIzin::count());
        $this->assertSame(90, JenisIzin::where('kode', KodeJenisIzin::Str->value)->value('ambang_hari'));
        $this->assertSame(90, JenisIzin::where('kode', KodeJenisIzin::Sip->value)->value('ambang_hari'));
    }

    public function test_seeder_idempoten_dan_tak_menimpa_ambang_kustom(): void
    {
        $this->seed(JenisIzinSeeder::class);
        JenisIzin::where('kode', KodeJenisIzin::Str->value)->update(['ambang_hari' => 120]);

        $this->seed(JenisIzinSeeder::class);

        $this->assertSame(4, JenisIzin::count());
        $this->assertSame(120, JenisIzin::where('kode', KodeJenisIzin::Str->value)->value('ambang_hari'));
    }
}
