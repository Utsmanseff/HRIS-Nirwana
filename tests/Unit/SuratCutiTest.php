<?php

namespace Tests\Unit;

use App\Enums\KodeJenisCuti;
use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use App\Models\ApprovalCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Support\SuratCuti;
use Database\Seeders\JenisCutiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(JenisCutiSeeder::class);
        Storage::fake('local');
    }

    /** Pengajuan dengan 2 baris approval (koordinator, hrd) — dipakai beberapa test. */
    protected function pengajuanDenganApproval(): PengajuanCuti
    {
        $p = PengajuanCuti::factory()->jenis(KodeJenisCuti::CutiSakit)->create();

        ApprovalCuti::create([
            'pengajuan_cuti_id' => $p->id, 'urutan' => 1,
            'approver_id' => Karyawan::factory()->create()->id,
            'peran' => PeranApproval::Koordinator, 'status' => StatusApproval::Setuju,
            'acted_at' => now(),
        ]);
        ApprovalCuti::create([
            'pengajuan_cuti_id' => $p->id, 'urutan' => 2,
            'approver_id' => Karyawan::factory()->create()->id,
            'peran' => PeranApproval::Hrd, 'status' => StatusApproval::Setuju,
            'acted_at' => now(),
        ]);

        return $p->fresh();
    }

    public function test_generate_menyimpan_path_dan_file_ada_di_storage(): void
    {
        $p = $this->pengajuanDenganApproval();

        $path = SuratCuti::generate($p);

        $this->assertNotEmpty($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_tiap_entri_tanda_tangan_punya_qr_non_null(): void
    {
        $p = $this->pengajuanDenganApproval();

        $ttd = SuratCuti::penandatangan($p);

        // Pemohon + 2 approval = 3 entri.
        $this->assertCount(3, $ttd);
        foreach ($ttd as $entri) {
            $this->assertNotEmpty($entri['qr']);
            $this->assertStringStartsWith('data:image/png;base64,', $entri['qr']);
        }
    }

    public function test_urutan_penandatangan_pemohon_dulu_lalu_approval_asc(): void
    {
        $p = $this->pengajuanDenganApproval();

        $ttd = SuratCuti::penandatangan($p);

        $this->assertSame('Pemohon', $ttd[0]['peran']);
        $this->assertSame('Koordinator', $ttd[1]['peran']);
        $this->assertSame('HRD', $ttd[2]['peran']);
    }
}
