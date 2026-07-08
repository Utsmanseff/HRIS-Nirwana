<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LampiranAset extends Model
{
    protected $table = 'lampiran_aset';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'berlaku_sampai' => 'date'];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }
}
