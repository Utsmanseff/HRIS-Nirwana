<?php

namespace Tests\Feature\Inventaris;

use App\Enums\Role;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\KategoriInventaris;
use App\Models\User;
use App\Notifications\PemeliharaanJatuhTempo;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KirimPengingatInventarisTest extends TestCase
{
    use RefreshDatabase;

    public function test_kirim_ke_tim_pemilik_dan_dedup(): void
    {
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $it = User::factory()->create();
        $it->assignRole(Role::It->value);
        $atem = User::factory()->create();
        $atem->assignRole(Role::Atem->value);

        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
        JadwalPemeliharaan::factory()->for($aset)->create([
            'interval_bulan' => 1,
            'terakhir_dilakukan' => Carbon::today()->subMonths(1)->subDays(2),
        ]);

        $this->artisan('inventaris:kirim-pengingat')->assertSuccessful();

        Notification::assertSentTo($it, PemeliharaanJatuhTempo::class);
        Notification::assertNotSentTo($atem, PemeliharaanJatuhTempo::class);
    }

    public function test_tanpa_jatuh_tempo_tak_kirim(): void
    {
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $it = User::factory()->create();
        $it->assignRole(Role::It->value);

        $kat = KategoriInventaris::factory()->create(['tim' => TimTeknis::It]);
        $aset = Aset::factory()->create(['kategori_inventaris_id' => $kat->id]);
        JadwalPemeliharaan::factory()->for($aset)->create([
            'interval_bulan' => 12,
            'terakhir_dilakukan' => Carbon::today()->subMonth(),
        ]);

        $this->artisan('inventaris:kirim-pengingat')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
