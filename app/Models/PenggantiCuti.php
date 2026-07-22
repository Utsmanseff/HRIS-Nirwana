<?php

namespace App\Models;

use App\Enums\StatusPengganti;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenggantiCuti extends Model
{
    use HasFactory;

    protected $table = 'pengganti_cuti';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status' => StatusPengganti::class,
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanCuti::class, 'pengajuan_cuti_id');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeAktif($q)
    {
        return $q->where('status', StatusPengganti::Aktif->value);
    }

    public function scopeUsulan($q)
    {
        return $q->where('status', StatusPengganti::Usulan->value);
    }
}
