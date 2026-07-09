<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanAbsensi extends Model
{
    protected $table = 'pengaturan_absensi';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'office_lat' => 'decimal:7',
            'office_long' => 'decimal:7',
            'radius_m' => 'integer',
            'max_akurasi_m' => 'integer',
        ];
    }

    /** Baris tunggal pengaturan (dibuat default bila belum ada). */
    public static function ambil(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'office_lat' => -6.9147440,
            'office_long' => 107.6098100,
            'radius_m' => 100,
            'max_akurasi_m' => 30,
        ]);
    }
}
