<?php

// app/Models/OrgUnit.php

namespace App\Models;

use App\Enums\OrgUnitTipe;
use App\Enums\StatusKaryawan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class OrgUnit extends Model
{
    use HasFactory;

    protected $table = 'org_units';

    protected $fillable = ['parent_id', 'nama', 'tipe', 'aktif', 'pakai_pengganti'];

    protected function casts(): array
    {
        return ['tipe' => OrgUnitTipe::class, 'aktif' => 'boolean', 'pakai_pengganti' => 'boolean'];
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

    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class, 'org_unit_id');
    }

    /** Kepala unit = karyawan aktif dengan level jabatan tertinggi (>=2). Null bila tak ada. */
    public function kepala(): ?Karyawan
    {
        return $this->karyawan()
            ->where('status', StatusKaryawan::Aktif->value)
            ->with('jabatan')
            ->get()
            ->filter(fn (Karyawan $k) => ($k->jabatan?->level?->value ?? 0) >= 2)
            ->sortByDesc(fn (Karyawan $k) => $k->jabatan->level->value)
            ->first();
    }

    /** Nama default jabatan pimpinan untuk unit ini (editable setelah dibuat). */
    public function namaPimpinan(): string
    {
        return match ($this->tipe) {
            OrgUnitTipe::Direktur => 'Direktur',
            OrgUnitTipe::Bidang => 'Kabid '.$this->nama,
            OrgUnitTipe::Bagian => 'Kabag '.$this->nama,
            OrgUnitTipe::Unit => 'Koordinator '.$this->nama,
        };
    }

    /** Jabatan pimpinan unit (1 per unit) — dibuat lazy dengan level sesuai tipe. */
    public function jabatanPimpinan(): Jabatan
    {
        $level = $this->tipe->levelPimpinan()->value;

        return Jabatan::firstOrCreate(
            ['org_unit_id' => $this->id, 'level' => $level],
            ['nama' => $this->namaPimpinan(), 'aktif' => true],
        );
    }

    /** Jabatan staff generik unit (target demote kepala lama) — dibuat lazy. */
    public function jabatanStaffDefault(): Jabatan
    {
        return Jabatan::firstOrCreate(
            ['org_unit_id' => $this->id, 'level' => 1, 'nama' => 'Staff '.$this->nama],
            ['aktif' => true],
        );
    }

    /**
     * Tetapkan $kar sebagai kepala unit ini.
     * Kepala lama (bila ada & beda orang) di-demote jadi staff unit ini.
     * $kar dipindah ke unit ini (org_unit_id) + jabatan pimpinan.
     */
    public function setKepala(Karyawan $kar): void
    {
        DB::transaction(function () use ($kar) {
            $lama = $this->kepala();
            if ($lama && $lama->id !== $kar->id) {
                $lama->update(['jabatan_id' => $this->jabatanStaffDefault()->id]);
            }
            $kar->update([
                'jabatan_id' => $this->jabatanPimpinan()->id,
                'org_unit_id' => $this->id,
            ]);
        });
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
