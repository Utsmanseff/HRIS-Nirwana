<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolaJadwal extends Model
{
    use HasFactory;

    protected $table = 'pola_jadwal';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['posisi' => 'integer'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateJadwal::class, 'template_id');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
