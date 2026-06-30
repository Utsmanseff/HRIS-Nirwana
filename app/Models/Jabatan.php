<?php

// app/Models/Jabatan.php

namespace App\Models;

use App\Enums\JabatanLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan';

    protected $fillable = ['nama', 'level', 'aktif'];

    protected function casts(): array
    {
        return ['level' => JabatanLevel::class, 'aktif' => 'boolean'];
    }

    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'jabatan_id');
    }
}
