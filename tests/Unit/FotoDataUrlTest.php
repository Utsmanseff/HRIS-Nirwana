<?php

namespace Tests\Unit;

use App\Support\FotoDataUrl;
use PHPUnit\Framework\TestCase;

class FotoDataUrlTest extends TestCase
{
    private function dataUrl(string $tipe, string $isi): string
    {
        return "data:image/$tipe;base64,".base64_encode($isi);
    }

    public function test_menerima_tipe_yang_diizinkan(): void
    {
        foreach (['webp', 'jpeg', 'png'] as $tipe) {
            $this->assertSame('halo', FotoDataUrl::dekode($this->dataUrl($tipe, 'halo'), 1024));
        }
    }

    public function test_menolak_kosong_dan_bukan_data_url(): void
    {
        $this->assertNull(FotoDataUrl::dekode(null, 1024));
        $this->assertNull(FotoDataUrl::dekode('', 1024));
        $this->assertNull(FotoDataUrl::dekode('https://contoh.test/a.webp', 1024));
        $this->assertNull(FotoDataUrl::dekode('halo', 1024));
    }

    public function test_menolak_tipe_di_luar_daftar(): void
    {
        $this->assertNull(FotoDataUrl::dekode($this->dataUrl('svg+xml', '<svg/>'), 1024));
        $this->assertNull(FotoDataUrl::dekode('data:text/html;base64,'.base64_encode('<b>'), 1024));
    }

    public function test_menolak_base64_rusak(): void
    {
        $this->assertNull(FotoDataUrl::dekode('data:image/webp;base64,!!bukan base64!!', 1024));
    }

    public function test_menolak_yang_melebihi_batas(): void
    {
        $besar = str_repeat('a', 2048);
        $this->assertNull(FotoDataUrl::dekode($this->dataUrl('webp', $besar), 1024));
        $this->assertSame($besar, FotoDataUrl::dekode($this->dataUrl('webp', $besar), 4096));
    }

    public function test_penjaga_panjang_menolak_tanpa_mendekode(): void
    {
        // 8 MB base64 harus ditolak oleh penjaga panjang, bukan setelah decode.
        $raksasa = 'data:image/webp;base64,'.str_repeat('A', 8 * 1024 * 1024);
        $this->assertNull(FotoDataUrl::dekode($raksasa, 5 * 1024 * 1024));
    }
}
