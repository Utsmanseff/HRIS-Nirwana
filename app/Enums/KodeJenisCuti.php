<?php

namespace App\Enums;

enum KodeJenisCuti: string
{
    case CutiTahunan = 'cuti_tahunan';
    case IzinBiasa = 'izin_biasa';
    case CutiSakit = 'cuti_sakit';
    case CutiMelahirkan = 'cuti_melahirkan';

    /** Subjudul singkat di chip pemilihan jenis. */
    public function subjudul(): string
    {
        return match ($this) {
            self::CutiTahunan => 'potong saldo',
            self::IzinBiasa => 'potong gaji',
            self::CutiSakit => 'lampir surat dokter',
            self::CutiMelahirkan => 'lampir dokumen',
        };
    }

    /** Keterangan panel info saat jenis dipilih (boleh HTML aman). */
    public function keterangan(): string
    {
        return match ($this) {
            self::CutiTahunan => 'Memotong <b>saldo cuti tahunan</b>. Syarat: masa kerja ≥ 1 tahun sejak kontrak pertama.',
            self::IzinBiasa => 'Memotong <b>gaji &amp; jasa</b> (bukan saldo cuti). Bisa kapan saja.',
            self::CutiSakit => '<b>Tidak</b> memotong saldo/gaji. Wajib lampirkan <b>surat dokter</b>. Boleh backdate.',
            self::CutiMelahirkan => '<b>Tidak</b> memotong saldo/gaji. Lampirkan <b>dokumen pendukung</b>.',
        };
    }

    /** Label tombol unggah lampiran; null bila lampiran tak wajib. */
    public function labelLampiran(): ?string
    {
        return match ($this) {
            self::CutiSakit => 'Unggah surat dokter',
            self::CutiMelahirkan => 'Unggah dokumen',
            default => null,
        };
    }
}
