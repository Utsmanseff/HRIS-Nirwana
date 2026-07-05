<?php

namespace Tests\Unit\Enums;

use App\Enums\KodeJenisCuti;
use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use App\Enums\StatusPengajuanCuti;
use PHPUnit\Framework\TestCase;

class CutiEnumTest extends TestCase
{
    public function test_kode_jenis_cuti_lengkap(): void
    {
        $this->assertSame('cuti_tahunan', KodeJenisCuti::CutiTahunan->value);
        $this->assertSame('izin_biasa', KodeJenisCuti::IzinBiasa->value);
        $this->assertSame('cuti_sakit', KodeJenisCuti::CutiSakit->value);
        $this->assertSame('cuti_melahirkan', KodeJenisCuti::CutiMelahirkan->value);
    }

    public function test_status_pengajuan_lengkap(): void
    {
        $this->assertSame(
            ['diajukan', 'diproses', 'disetujui', 'ditolak', 'dibatalkan'],
            array_map(fn ($c) => $c->value, StatusPengajuanCuti::cases()),
        );
    }

    public function test_status_approval_dan_peran(): void
    {
        $this->assertSame(['menunggu', 'setuju', 'tolak'], array_map(fn ($c) => $c->value, StatusApproval::cases()));
        $this->assertSame(['koordinator', 'kabid', 'hrd', 'direktur'], array_map(fn ($c) => $c->value, PeranApproval::cases()));
    }
}
