<?php

namespace Tests\Feature\Cuti;

use App\Models\HariLibur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HariLiburModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_rentang_menyaring_tanggal(): void
    {
        HariLibur::create(['tanggal' => '2026-03-10', 'nama' => 'Cuti Bersama']);
        HariLibur::create(['tanggal' => '2026-06-01', 'nama' => 'Libur Nasional']);

        $hasil = HariLibur::dalamRentang('2026-03-01', '2026-03-31')->get();

        $this->assertCount(1, $hasil);
        $this->assertSame('Cuti Bersama', $hasil->first()->nama);
    }
}
