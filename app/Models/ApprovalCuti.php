<?php

// app/Models/ApprovalCuti.php

namespace App\Models;

use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalCuti extends Model
{
    protected $table = 'approval_cuti';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'peran' => PeranApproval::class,
            'status' => StatusApproval::class,
            'acted_at' => 'datetime',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanCuti::class, 'pengajuan_cuti_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'approver_id');
    }
}
