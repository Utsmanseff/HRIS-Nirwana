<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LampiranTiket extends Model
{
    protected $table = 'lampiran_tiket';

    protected $guarded = ['id'];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class);
    }
}
