<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiAset extends Model
{
    protected $table = 'mutasi_aset';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }

    public function dariUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'dari_unit_id');
    }

    public function keUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'ke_unit_id');
    }

    public function oleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh');
    }
}
