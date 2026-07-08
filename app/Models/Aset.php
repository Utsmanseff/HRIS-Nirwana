<?php

namespace App\Models;

use App\Enums\StatusAset;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'aset';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_pengadaan' => 'date',
            'nilai_perolehan' => 'decimal:2',
            'status' => StatusAset::class,
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriInventaris::class, 'kategori_inventaris_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'penanggung_jawab_id');
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(MutasiAset::class)->latest('tanggal');
    }

    public function jadwalPemeliharaan(): HasMany
    {
        return $this->hasMany(JadwalPemeliharaan::class);
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(LampiranAset::class);
    }

    /** Tim penangan derived dari kategori. */
    public function tim(): Attribute
    {
        return Attribute::get(fn () => $this->kategori?->tim);
    }

    /** Filter aset milik tim (via kategori). */
    public function scopeTim($q, array $timNilai)
    {
        return $q->whereHas('kategori', fn ($k) => $k->whereIn('tim', $timNilai));
    }
}
