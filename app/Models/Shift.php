<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shift';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['toleransi_telat' => 'integer', 'aktif' => 'boolean'];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    /** Shift malam: jam selesai lebih awal dari jam mulai. */
    public function lintasHari(): bool
    {
        return $this->jam_selesai < $this->jam_mulai;
    }

    public function scopeAktif($q)
    {
        return $q->where('aktif', true);
    }
}
