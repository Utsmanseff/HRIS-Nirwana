<?php

// app/Models/JenisCuti.php

namespace App\Models;

use App\Enums\KodeJenisCuti;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisCuti extends Model
{
    use HasFactory;

    protected $table = 'jenis_cuti';

    protected $fillable = ['kode', 'nama', 'potong_saldo', 'efek_penggajian', 'butuh_lampiran', 'boleh_backdate', 'aktif'];

    protected function casts(): array
    {
        return [
            'kode' => KodeJenisCuti::class,
            'potong_saldo' => 'boolean',
            'butuh_lampiran' => 'boolean',
            'boleh_backdate' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class);
    }

    public function scopeAktif($q)
    {
        return $q->where('aktif', true);
    }
}
