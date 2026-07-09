<?php

namespace App\Models;

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
        return ['tanggal_jangkar' => 'date'];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function baris(): HasMany
    {
        return $this->hasMany(PolaJadwal::class, 'template_id');
    }
}
