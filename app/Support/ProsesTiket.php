<?php

namespace App\Support;

use App\Enums\JenisTiket;
use App\Enums\StatusAset;
use App\Enums\StatusTiket;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Pusat lifecycle tiket. Transisi maju saja (no reopen). Sinkron status aset & jadwal (no double-input). */
class ProsesTiket
{
    /** baru → diproses. Set waktu_respon (sekali). Perbaikan+aset → aset dalam_perbaikan. */
    public static function mulai(Tiket $tiket, User $oleh): void
    {
        if ($tiket->status !== StatusTiket::Baru) {
            throw new RuntimeException('Tiket hanya bisa diproses dari status Baru.');
        }

        DB::transaction(function () use ($tiket) {
            $tiket->update([
                'status' => StatusTiket::Diproses,
                'waktu_respon' => $tiket->waktu_respon ?? now(),
            ]);
            self::asetDalamPerbaikan($tiket);
        });
    }

    /** baru|diproses → selesai. Isi waktu_selesai/penyelesai/catatan + waktu_respon (bila null) + integrasi aset. */
    public static function selesai(Tiket $tiket, User $oleh, ?string $catatan = null): void
    {
        if (! in_array($tiket->status, StatusTiket::aktif(), true)) {
            throw new RuntimeException('Tiket sudah tidak aktif, tak bisa diselesaikan.');
        }

        DB::transaction(function () use ($tiket, $oleh, $catatan) {
            $tiket->update([
                'status' => StatusTiket::Selesai,
                'waktu_respon' => $tiket->waktu_respon ?? now(),
                'waktu_selesai' => now(),
                'penyelesai_id' => $oleh->id,
                'catatan_penyelesaian' => $catatan,
            ]);
            self::sinkronSelesai($tiket);
        });

        // Notif ke pelapor (diisi di Task 9).
        if ($tiket->pelapor_id) {
            self::notifSelesai($tiket);
        }
    }

    /** → batal. */
    public static function batal(Tiket $tiket): void
    {
        if (! in_array($tiket->status, StatusTiket::aktif(), true)) {
            throw new RuntimeException('Hanya tiket aktif yang bisa dibatalkan.');
        }
        $tiket->update(['status' => StatusTiket::Batal]);
    }

    /** Perbaikan + aset tertaut → aset dalam_perbaikan (kecuali afkir). */
    public static function asetDalamPerbaikan(Tiket $tiket): void
    {
        if ($tiket->jenis === JenisTiket::Perbaikan && $tiket->inventaris_id) {
            $aset = $tiket->aset;
            if ($aset && $aset->status !== StatusAset::Afkir) {
                $aset->update(['status' => StatusAset::DalamPerbaikan->value]);
            }
        }
    }

    /** Integrasi saat selesai: perbaikan→aset baik; pemeliharaan+jadwal→update terakhir_dilakukan. */
    private static function sinkronSelesai(Tiket $tiket): void
    {
        if ($tiket->jenis === JenisTiket::Perbaikan && $tiket->inventaris_id) {
            $aset = $tiket->aset;
            if ($aset && $aset->status === StatusAset::DalamPerbaikan) {
                $aset->update(['status' => StatusAset::Baik->value]);
            }
        }

        if ($tiket->jenis === JenisTiket::Pemeliharaan && $tiket->jadwal_pemeliharaan_id) {
            $tiket->jadwalPemeliharaan?->update(['terakhir_dilakukan' => now()]);
        }
    }

    /** Placeholder notif — diisi di Task 9 (TiketSelesai → pelapor). */
    private static function notifSelesai(Tiket $tiket): void
    {
        // Diisi Task 9.
    }
}
