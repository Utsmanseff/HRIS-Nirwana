<?php

// app/Models/OrgUnit.php

namespace App\Models;

use App\Enums\OrgUnitTipe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgUnit extends Model
{
    use HasFactory;

    protected $table = 'org_units';

    protected $fillable = ['parent_id', 'nama', 'tipe', 'aktif'];

    protected function casts(): array
    {
        return ['tipe' => OrgUnitTipe::class, 'aktif' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'org_unit_id');
    }

    public function scopeAkar($q)
    {
        return $q->whereNull('parent_id');
    }

    /** Id unit ini + seluruh turunannya (tabel org kecil — traversal di PHP). */
    public static function denganTurunan(int $unitId): array
    {
        $semua = static::get(['id', 'parent_id']);
        $ids = [$unitId];
        $antrian = [$unitId];
        while ($antrian) {
            $anak = $semua->whereIn('parent_id', $antrian)->pluck('id')->all();
            $ids = array_merge($ids, $anak);
            $antrian = $anak;
        }

        return $ids;
    }
}
