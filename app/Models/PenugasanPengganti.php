<?php

namespace App\Models;

use App\Enums\StatusPengganti;
use App\Enums\TipePengganti;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rencana penugasan pengganti. Dua tipe:
 * - cuti     : menutup shift pemohon selama masa cutinya (rentang tertutup).
 * - lowongan : menutup shift karyawan nonaktif (rentang terbuka, tanggal_selesai null).
 */
class PenugasanPengganti extends Model
{
    use HasFactory;

    protected $table = 'penugasan_pengganti';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status' => StatusPengganti::class,
            'tipe' => TipePengganti::class,
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanCuti::class, 'pengajuan_cuti_id');
    }

    /** Si pengganti (yang mengambil shift). */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    /** Yang digantikan: pemohon cuti, atau karyawan nonaktif. */
    public function karyawanDigantikan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_digantikan_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeAktif($q)
    {
        return $q->where('status', StatusPengganti::Aktif->value);
    }

    public function scopeUsulan($q)
    {
        return $q->where('status', StatusPengganti::Usulan->value);
    }

    public function scopeCuti($q)
    {
        return $q->where('tipe', TipePengganti::Cuti->value);
    }

    public function scopeLowongan($q)
    {
        return $q->where('tipe', TipePengganti::Lowongan->value);
    }

    /** Rentang tanpa ujung (lowongan yang belum ditutup). */
    public function terbuka(): bool
    {
        return $this->tanggal_selesai === null;
    }

    /** Teks kolom Keterangan laporan absensi / Jadwal Saya. */
    public function label(): string
    {
        $nama = $this->karyawanDigantikan?->nama_lengkap ?? '—';

        return $this->tipe->prefiksKeterangan().' — '.$nama;
    }
}
