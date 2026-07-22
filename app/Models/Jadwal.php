<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwal';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penggantiCuti(): BelongsTo
    {
        return $this->belongsTo(PenggantiCuti::class, 'pengganti_cuti_id');
    }

    /** Baris jadwal hasil salinan pengganti cuti (bukan jadwal biasa). */
    public function scopeSalinanPengganti($q)
    {
        return $q->whereNotNull('pengganti_cuti_id');
    }
}
