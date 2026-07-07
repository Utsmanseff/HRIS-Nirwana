<?php

namespace App\Models;

use App\Enums\PeranApproval;
use App\Enums\StatusApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalSanksi extends Model
{
    protected $table = 'approval_sanksi';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'peran' => PeranApproval::class,
            'status' => StatusApproval::class,
            'acted_at' => 'datetime',
        ];
    }

    public function sanksi(): BelongsTo
    {
        return $this->belongsTo(SanksiDisiplin::class, 'sanksi_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'approver_id');
    }
}
