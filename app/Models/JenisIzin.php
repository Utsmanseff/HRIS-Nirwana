<?php

// app/Models/JenisIzin.php

namespace App\Models;

use App\Enums\KodeJenisIzin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisIzin extends Model
{
    use HasFactory;

    protected $table = 'jenis_izin';

    protected $fillable = ['kode', 'nama', 'ambang_hari', 'aktif'];

    protected function casts(): array
    {
        return [
            'kode' => KodeJenisIzin::class,
            'ambang_hari' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function izin(): HasMany
    {
        return $this->hasMany(IzinKaryawan::class);
    }

    public function scopeAktif($q)
    {
        return $q->where('aktif', true);
    }
}
