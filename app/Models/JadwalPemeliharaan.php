<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class JadwalPemeliharaan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_pemeliharaan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['terakhir_dilakukan' => 'date', 'aktif' => 'boolean'];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }

    /** Jatuh tempo berikutnya = terakhir + interval; null bila belum pernah (perlu dijadwalkan). */
    public function berikutnya(): ?Carbon
    {
        if (! $this->terakhir_dilakukan) {
            return null;
        }

        return $this->terakhir_dilakukan->copy()->addMonths($this->interval_bulan);
    }
}
