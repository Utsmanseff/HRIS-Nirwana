<?php

// app/Models/Kontrak.php

namespace App\Models;

use App\Enums\JenisKontrak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kontrak extends Model
{
    use HasFactory;

    protected $table = 'kontrak';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['jenis' => JenisKontrak::class, 'tanggal_mulai' => 'date', 'tanggal_akhir' => 'date'];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }
}
