<?php

namespace App\Models;

use App\Enums\TimTeknis;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriInventaris extends Model
{
    use HasFactory;

    protected $table = 'kategori_inventaris';

    protected $fillable = ['nama', 'tim', 'aktif'];

    protected function casts(): array
    {
        return ['tim' => TimTeknis::class, 'aktif' => 'boolean'];
    }

    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class);
    }

    public function scopeTim($q, array $timNilai)
    {
        return $q->whereIn('tim', $timNilai);
    }
}
