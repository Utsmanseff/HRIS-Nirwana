<?php

namespace App\Models;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SanksiDisiplin extends Model
{
    use HasFactory;

    protected $table = 'sanksi_disiplin';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tingkat' => TingkatSanksi::class,
            'status' => StatusSanksi::class,
            'tanggal_kejadian' => 'date',
            'tanggal_terbit' => 'date',
            'berlaku_sampai' => 'date',
            'dicabut_pada' => 'datetime',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function pengusul(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'pengusul_id');
    }

    public function penerbit(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterbitkan_oleh');
    }

    public function pencabut(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicabut_oleh');
    }
}
