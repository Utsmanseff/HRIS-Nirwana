<?php

// app/Models/Karyawan.php

namespace App\Models;

use App\Enums\AlasanNonaktif;
use App\Enums\JenisKelamin;
use App\Enums\JenisKontrak;
use App\Enums\StatusKaryawan;
use App\Enums\StatusNikah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date', 'tanggal_masuk' => 'date', 'tanggal_nonaktif' => 'date',
            'jenis_kelamin' => JenisKelamin::class, 'status_nikah' => StatusNikah::class,
            'status' => StatusKaryawan::class, 'alasan_nonaktif' => AlasanNonaktif::class,
        ];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'atasan_id');
    }

    public function bawahan(): HasMany
    {
        return $this->hasMany(self::class, 'atasan_id');
    }

    public function kontrak(): HasMany
    {
        return $this->hasMany(Kontrak::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function scopeAktif($q)
    {
        return $q->where('status', StatusKaryawan::Aktif->value);
    }

    public function kontrakTerakhir(): ?Kontrak
    {
        return $this->kontrak()->orderByDesc('tanggal_mulai')->orderByDesc('id')->first();
    }

    public function anchorCutiTahunan(): ?Carbon
    {
        return $this->kontrak()->where('jenis', JenisKontrak::Pkwt->value)
            ->orderBy('tanggal_mulai')->orderBy('id')->first()?->tanggal_mulai;
    }
}
