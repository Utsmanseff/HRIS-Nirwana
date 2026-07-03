<?php

// app/Models/Karyawan.php

namespace App\Models;

use App\Enums\AlasanNonaktif;
use App\Enums\JabatanLevel;
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
            'sip_berlaku_mulai' => 'date', 'sip_berlaku_akhir' => 'date',
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

    protected static function booted(): void
    {
        static::saving(function (self $k) {
            // org_unit mengikuti jabatan; hanya isi bila belum diset eksplisit.
            if (empty($k->org_unit_id) && $k->jabatan_id) {
                $k->org_unit_id = Jabatan::whereKey($k->jabatan_id)->value('org_unit_id');
            }
        });
    }

    /** Atasan langsung, dihitung dari struktur (bukan kolom). */
    public function atasanDerived(): ?self
    {
        $unit = $this->orgUnit;
        if (! $unit) {
            return null;
        }

        $kepala = $unit->kepala();
        $levelSaya = $this->jabatan?->level?->value ?? 0;

        // Ada kepala di unit ini yang levelnya di atasku → dia atasanku.
        if ($kepala && $kepala->id !== $this->id && $kepala->jabatan->level->value > $levelSaya) {
            return $kepala;
        }

        // Aku kepala unit ini (atau tak ada yang lebih tinggi) → naik ke unit induk, skip yang kosong.
        $induk = $unit->parent;
        while ($induk) {
            $kep = $induk->kepala();
            if ($kep && $kep->id !== $this->id) {
                return $kep;
            }
            $induk = $induk->parent;
        }

        return null;
    }

    /** True bila karyawan ini kepala unit dan ada anggota lain di unit/turunannya. */
    public function punyaBawahan(): bool
    {
        $unit = $this->orgUnit;
        if (! $unit || ($this->jabatan?->level?->value ?? 0) < JabatanLevel::Koordinator->value) {
            return false;
        }
        $kepala = $unit->kepala();
        if (! $kepala || $kepala->id !== $this->id) {
            return false;
        }

        return static::query()
            ->whereIn('org_unit_id', OrgUnit::denganTurunan($unit->id))
            ->where('status', StatusKaryawan::Aktif->value)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public function kontrak(): HasMany
    {
        return $this->hasMany(Kontrak::class);
    }

    /** Kontrak terbaru (tanggal_mulai lalu id) — bisa di-eager-load & difilter SQL. */
    public function kontrakTerbaru(): HasOne
    {
        return $this->hasOne(Kontrak::class)->latestOfMany(['tanggal_mulai', 'id']);
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

    /**
     * Saringan bersama layar daftar karyawan & laporan ekspor.
     * Kunci filter: cari, unit_id, level, kontrak_jenis, status ('' / 'semua' = tanpa filter status).
     */
    public function scopeSaring($query, array $f)
    {
        $cari = trim((string) ($f['cari'] ?? ''));
        $status = (string) ($f['status'] ?? '');

        return $query
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama_lengkap', 'like', "%{$cari}%")
                ->orWhere('nip', 'like', "%{$cari}%")))
            ->when(! empty($f['unit_id']), fn ($q) => $q->whereIn('org_unit_id', OrgUnit::denganTurunan((int) $f['unit_id'])))
            ->when(! empty($f['level']), fn ($q) => $q->whereHas('jabatan', fn ($w) => $w->where('level', (int) $f['level'])))
            ->when(! empty($f['kontrak_jenis']), fn ($q) => $q->whereHas('kontrakTerbaru', fn ($w) => $w->where('jenis', $f['kontrak_jenis'])))
            ->when($status !== '' && $status !== 'semua', fn ($q) => $q->where('status', $status));
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
