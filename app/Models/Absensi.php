<?php

namespace App\Models;

use App\Support\Durasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $guarded = ['id'];

    /** Durasi (menit) di atas ini = anomali durasi tak wajar. */
    public const BATAS_ANOMALI_MENIT = 16 * 60;

    /**
     * Durasi (menit) di bawah ini = anomali sesi kilat. Nyaris selalu salah pencet:
     * swipe pulang terpanggil sekali lagi tepat setelah swipe masuk, menghasilkan
     * baris "15:47 → 15:47, 0m" yang terlihat sah sebagai kehadiran.
     */
    public const BATAS_SESI_KILAT_MENIT = 5;

    protected function casts(): array
    {
        return [
            'tanggal_kerja' => 'date',
            'jam_masuk' => 'datetime',
            'jam_pulang' => 'datetime',
            'lat_masuk' => 'decimal:7',
            'long_masuk' => 'decimal:7',
            'lat_pulang' => 'decimal:7',
            'long_pulang' => 'decimal:7',
            'akurasi_masuk' => 'float',
            'akurasi_pulang' => 'float',
            'wajah_verif_masuk' => 'boolean',
            'wajah_verif_pulang' => 'boolean',
            'flag_lokasi_masuk' => 'array',
            'flag_lokasi_pulang' => 'array',
            'telat_menit' => 'integer',
            'pulang_cepat_menit' => 'integer',
            'shift_toleransi' => 'integer',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /** Sesi belum ditutup (belum absen pulang). */
    public function sesiAktif(): bool
    {
        return $this->jam_pulang === null;
    }

    /** Ada snapshot shift (mode evaluasi telat), bukan mode catat. */
    public function adaShift(): bool
    {
        return $this->shift_mulai !== null;
    }

    /** Total menit kerja (pulang − masuk); null bila sesi masih aktif. */
    public function totalMenit(): ?int
    {
        return $this->jam_pulang
            ? (int) $this->jam_masuk->diffInMinutes($this->jam_pulang)
            : null;
    }

    /** Label jam kerja "Xj Ym" (atau '-' bila sesi belum tutup). */
    public function jamKerjaLabel(): string
    {
        $m = $this->totalMenit();

        return $m === null ? '-' : Durasi::label($m);
    }

    /** Anomali: sesi nyangkut (aktif & tanggal lampau), durasi tak wajar, atau sesi kilat. */
    public function anomali(): bool
    {
        if ($this->sesiAktif()) {
            return $this->tanggal_kerja->lt(now()->startOfDay());
        }

        $menit = $this->totalMenit();

        return $menit > self::BATAS_ANOMALI_MENIT || $menit < self::BATAS_SESI_KILAT_MENIT;
    }

    public function scopeAktif($q)
    {
        return $q->whereNull('jam_pulang');
    }

    /** Status rekap derived: anomali | telat | pulang_cepat | normal. */
    public function statusRekap(): string
    {
        if ($this->anomali()) {
            return 'anomali';
        }
        if ($this->telat_menit) {
            return 'telat';
        }
        if ($this->pulang_cepat_menit) {
            return 'pulang_cepat';
        }

        return 'normal';
    }

    /**
     * Label + kelas badge untuk status rekap.
     *
     * @return array{0:string,1:string}
     */
    public function labelStatus(): array
    {
        return match ($this->statusRekap()) {
            'anomali' => ['Anomali', 'badge-danger'],
            'telat' => ['Telat'.($this->telat_menit ? ' '.Durasi::label($this->telat_menit) : ''), 'badge-warning'],
            'pulang_cepat' => ['Pulang cepat'.($this->pulang_cepat_menit ? ' '.Durasi::label($this->pulang_cepat_menit) : ''), 'badge-warning'],
            default => [$this->adaShift() ? 'Normal' : 'Tercatat', 'badge-success'],
        };
    }
}
