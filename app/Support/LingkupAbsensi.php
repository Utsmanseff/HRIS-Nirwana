<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lingkup akses laporan absensi:
 * - HRD & Admin Sistem → semua unit.
 * - Koordinator (punya bawahan) → hanya subtree unit yang dipimpin.
 */
class LingkupAbsensi
{
    public static function bisaSemua(User $user): bool
    {
        return $user->hasRole(Role::Hrd->value)
            || $user->hasRole(Role::StaffHr->value)
            || $user->hasRole(Role::AdminSistem->value);
    }

    /** ID semua unit dalam subtree yang dipimpin koordinator (kosong bila tak memimpin). */
    public static function subtreeIds(?Karyawan $kar): array
    {
        if (! $kar) {
            return [];
        }

        return $kar->unitDipimpin()
            ->flatMap(fn (OrgUnit $u) => OrgUnit::denganTurunan($u->id))
            ->unique()->values()->all();
    }

    /**
     * Unit filter efektif untuk query.
     * - bisaSemua → hormati pilihan (null = semua).
     * - koordinator → pakai pilihan bila dalam subtree, else akar subtree (batasi).
     */
    public static function unitEfektif(User $user, ?int $diminta): ?int
    {
        if (self::bisaSemua($user)) {
            return $diminta;
        }

        $sub = self::subtreeIds($user->karyawan);
        if ($diminta && in_array($diminta, $sub, true)) {
            return $diminta;
        }

        return $user->karyawan?->unitDipimpin()->first()?->id;
    }

    /** Opsi unit untuk dropdown, dibatasi lingkup user. */
    public static function opsiUnit(User $user): Collection
    {
        if (self::bisaSemua($user)) {
            return OrgUnit::orderBy('nama')->get();
        }

        return OrgUnit::whereIn('id', self::subtreeIds($user->karyawan))->orderBy('nama')->get();
    }
}
