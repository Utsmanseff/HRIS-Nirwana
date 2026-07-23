<?php

namespace App\Models;

use App\Enums\ModeTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateJadwal extends Model
{
    use HasFactory;

    protected $table = 'template_jadwal';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal_jangkar' => 'date', 'mode' => ModeTemplate::class];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    /** Semua pola milik satu unit, urut nama. */
    public function scopeUntukUnit($q, int $unitId)
    {
        return $q->where('org_unit_id', $unitId)->orderBy('nama');
    }

    public function baris(): HasMany
    {
        return $this->hasMany(PolaJadwal::class, 'template_id');
    }
}
