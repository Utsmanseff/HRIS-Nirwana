<?php

// app/Models/PenyesuaianSaldo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyesuaianSaldo extends Model
{
    use HasFactory;

    protected $table = 'penyesuaian_saldo';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['periode_mulai' => 'date', 'delta' => 'integer'];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
