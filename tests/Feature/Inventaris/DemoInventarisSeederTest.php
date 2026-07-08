<?php

namespace Tests\Feature\Inventaris;

use App\Models\Aset;
use App\Models\JadwalPemeliharaan;
use App\Models\KategoriInventaris;
use Database\Seeders\DemoInventarisSeeder;
use Database\Seeders\DemoSdmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoInventarisSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_menghasilkan_aset_dan_idempoten(): void
    {
        $this->seed([RoleSeeder::class, DemoSdmSeeder::class, DemoInventarisSeeder::class]);

        $jumlahKategori = KategoriInventaris::count();
        $jumlahAset = Aset::count();
        $this->assertGreaterThan(0, $jumlahKategori);
        $this->assertGreaterThan(0, $jumlahAset);
        $this->assertGreaterThan(0, JadwalPemeliharaan::count());

        // Idempoten: seed ulang tak menambah baris.
        $this->seed(DemoInventarisSeeder::class);
        $this->assertSame($jumlahKategori, KategoriInventaris::count());
        $this->assertSame($jumlahAset, Aset::count());
    }
}
