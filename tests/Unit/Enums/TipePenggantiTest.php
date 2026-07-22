<?php

namespace Tests\Unit\Enums;

use App\Enums\TipePengganti;
use PHPUnit\Framework\TestCase;

class TipePenggantiTest extends TestCase
{
    public function test_dua_kasus_dengan_nilai_string(): void
    {
        $this->assertSame('cuti', TipePengganti::Cuti->value);
        $this->assertSame('lowongan', TipePengganti::Lowongan->value);
        $this->assertCount(2, TipePengganti::cases());
    }

    public function test_label_dan_prefiks_keterangan(): void
    {
        $this->assertSame('Pengganti Cuti', TipePengganti::Cuti->label());
        $this->assertSame('Isi Jadwal Kosong', TipePengganti::Lowongan->label());
        $this->assertSame('Pengganti cuti', TipePengganti::Cuti->prefiksKeterangan());
        $this->assertSame('Mengisi jadwal kosong', TipePengganti::Lowongan->prefiksKeterangan());
    }
}
