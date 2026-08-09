<?php

// app/Models/IzinKaryawan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IzinKaryawan extends Model
{
    use HasFactory;

    protected $table = 'izin_karyawan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['berlaku_mulai' => 'date', 'berlaku_akhir' => 'date'];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisIzin::class, 'jenis_izin_id');
    }
}
