<?php

namespace App\Models;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tiket extends Model
{
    use HasFactory;

    protected $table = 'tiket';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'jenis' => JenisTiket::class,
            'tim' => TimTeknis::class,
            'prioritas' => PrioritasTiket::class,
            'status' => StatusTiket::class,
            'waktu_lapor' => 'datetime',
            'waktu_respon' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'inventaris_id');
    }

    public function jadwalPemeliharaan(): BelongsTo
    {
        return $this->belongsTo(JadwalPemeliharaan::class, 'jadwal_pemeliharaan_id');
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'pelapor_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penyelesai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penyelesai_id');
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(LampiranTiket::class);
    }

    /** Menit dari lapor → respon pertama; null bila belum direspon. */
    public function menitRespon(): ?int
    {
        return $this->waktu_respon
            ? (int) $this->waktu_lapor->diffInMinutes($this->waktu_respon)
            : null;
    }

    /** Menit dari lapor → selesai; null bila belum selesai. */
    public function menitPenyelesaian(): ?int
    {
        return $this->waktu_selesai
            ? (int) $this->waktu_lapor->diffInMinutes($this->waktu_selesai)
            : null;
    }

    /** Nomor tiket berikutnya (TKT-{tahun}-{urut4}) berdasarkan tiket tahun berjalan. */
    public static function buatNomor(): string
    {
        $tahun = now()->year;
        $prefix = "TKT-{$tahun}-";
        $terakhir = static::where('nomor', 'like', $prefix.'%')->max('nomor');
        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    /** Filter tiket milik tim tertentu. */
    public function scopeTim($q, array $timNilai)
    {
        return $q->whereIn('tim', $timNilai);
    }
}
