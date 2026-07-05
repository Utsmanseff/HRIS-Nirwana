<?php

// app/Models/HariLibur.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $table = 'hari_libur';

    protected $fillable = ['tanggal', 'nama'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function scopeDalamRentang($q, $mulai, $selesai)
    {
        return $q->whereBetween('tanggal', [$mulai, $selesai])->orderBy('tanggal');
    }
}
