<?php

// app/Models/PengajuanCuti.php

namespace App\Models;

use App\Enums\StatusPengajuanCuti;
use App\Enums\StatusApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanCuti extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_cuti';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status' => StatusPengajuanCuti::class,
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function jenisCuti(): BelongsTo
    {
        return $this->belongsTo(JenisCuti::class);
    }

    public function approval(): HasMany
    {
        return $this->hasMany(ApprovalCuti::class)->orderBy('urutan');
    }

    public function pengganti(): HasMany
    {
        return $this->hasMany(PenugasanPengganti::class, 'pengajuan_cuti_id')->orderBy('tanggal_mulai');
    }

    /** Baris approval aktif = status menunggu dengan urutan terkecil (sequential). */
    public function tahapAktif(): ?ApprovalCuti
    {
        return $this->approval()
            ->where('status', StatusApproval::Menunggu)
            ->orderBy('urutan')
            ->first();
    }

    public function pembatal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }
}
