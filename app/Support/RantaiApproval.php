<?php

// app/Support/RantaiApproval.php

namespace App\Support;

use App\Enums\JabatanLevel;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Models\Karyawan;
use Illuminate\Support\Collection;

class RantaiApproval
{
    /**
     * Susun rantai approver terurut (bawah→atas, HRD/Direktur terakhir).
     *
     * @return Collection<int, array{urutan:int, approver:Karyawan, peran:PeranApproval}>
     */
    public static function susun(Karyawan $pemohon): Collection
    {
        $steps = collect();

        // Kasus khusus: pemohon ber-role HRD → hanya Direktur (final).
        if (self::pemohonPunyaRole($pemohon, Role::Hrd)) {
            $dir = self::pemegangRole(Role::Direktur);
            if ($dir) {
                $steps->push(['approver' => $dir, 'peran' => PeranApproval::Direktur]);
            }

            return self::beriUrutan($steps);
        }

        // Naiki rantai atasan derived hanya bila pemohon di bawah Kabid.
        if (($pemohon->jabatan?->level?->value ?? 0) < JabatanLevel::Kabid->value) {
            $current = $pemohon->atasanDerived();
            while ($current) {
                $lvl = $current->jabatan?->level?->value ?? 0;
                if ($lvl >= JabatanLevel::Direktur->value) {
                    break; // Direktur tak jadi approver cuti umum
                }
                $steps->push([
                    'approver' => $current,
                    'peran' => $lvl >= JabatanLevel::Kabid->value ? PeranApproval::Kabid : PeranApproval::Koordinator,
                ]);
                if ($lvl >= JabatanLevel::Kabid->value) {
                    break; // berhenti setelah mencakup Kabid/Kabag
                }
                $current = $current->atasanDerived();
            }
        }

        // Append HRD final (kecuali kalau HRD sudah ada di rantai / pemohon sendiri).
        $hrd = self::pemegangRole(Role::Hrd);
        if ($hrd && $hrd->id !== $pemohon->id && ! $steps->contains(fn ($s) => $s['approver']->id === $hrd->id)) {
            $steps->push(['approver' => $hrd, 'peran' => PeranApproval::Hrd]);
        }

        return self::beriUrutan($steps);
    }

    private static function pemohonPunyaRole(Karyawan $kar, Role $role): bool
    {
        return (bool) $kar->user?->hasRole($role->value);
    }

    /** Karyawan (aktif) pemegang sebuah role via akun user. HRD/Direktur dijamin 1 orang. */
    private static function pemegangRole(Role $role): ?Karyawan
    {
        return Karyawan::query()
            ->whereHas('user', fn ($q) => $q->role($role->value))
            ->first();
    }

    /** @param Collection<int, array{approver:Karyawan, peran:PeranApproval}> $steps */
    private static function beriUrutan(Collection $steps): Collection
    {
        return $steps->values()->map(fn ($s, $i) => [
            'urutan' => $i + 1,
            'approver' => $s['approver'],
            'peran' => $s['peran'],
        ]);
    }
}
