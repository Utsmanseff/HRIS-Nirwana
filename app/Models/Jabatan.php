<?php

// app/Models/Jabatan.php

namespace App\Models;

use App\Enums\JabatanLevel;
use App\Enums\RutePengawas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan';

    protected $fillable = ['nama', 'level', 'org_unit_id', 'aktif', 'rute_pengawas'];

    protected function casts(): array
    {
        return ['level' => JabatanLevel::class, 'aktif' => 'boolean', 'rute_pengawas' => RutePengawas::class];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'jabatan_id');
    }

    /** Jabatan ini berwenang mengusulkan sanksi untuk seluruh karyawan. */
    public function adalahPengawas(): bool
    {
        return $this->rute_pengawas !== null;
    }

    public function scopePimpinan($q)
    {
        return $q->where('level', '>=', JabatanLevel::Koordinator->value);
    }

    public function scopeStaff($q)
    {
        return $q->where('level', JabatanLevel::Staff->value);
    }
}
