<?php

namespace App\Support;

use App\Enums\JabatanLevel;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Models\ApprovalSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RantaiSanksi
{
    /**
     * Susun rantai approver sanksi dari PENGUSUL naik ke atas, HRD terakhir (yang terbit).
     * Direktur tak pernah jadi approver. HRD pengusul → self (terbit langsung).
     *
     * @return Collection<int, array{urutan:int, approver:Karyawan, peran:PeranApproval}>
     */
    public static function susun(Karyawan $pengusul): Collection
    {
        // HRD buat-langsung → dirinya sendiri tahap final.
        if ($pengusul->user?->hasRole(Role::Hrd->value)) {
            return self::beriUrutan(collect([
                ['approver' => $pengusul, 'peran' => PeranApproval::Hrd],
            ]));
        }

        $steps = collect();

        // Pengusul di bawah Kabid (koordinator) → naik sampai dapat Kabid (inklusif).
        if (($pengusul->jabatan?->level?->value ?? 0) < JabatanLevel::Kabid->value) {
            $current = $pengusul->atasanDerived();
            while ($current) {
                $lvl = $current->jabatan?->level?->value ?? 0;
                if ($lvl >= JabatanLevel::Direktur->value) {
                    break; // Direktur tak jadi approver
                }
                $steps->push([
                    'approver' => $current,
                    'peran' => $lvl >= JabatanLevel::Kabid->value ? PeranApproval::Kabid : PeranApproval::Koordinator,
                ]);
                if ($lvl >= JabatanLevel::Kabid->value) {
                    break;
                }
                $current = $current->atasanDerived();
            }
        }

        // Append HRD final.
        $hrd = self::pemegangRole(Role::Hrd);
        if ($hrd && ! $steps->contains(fn ($s) => $s['approver']->id === $hrd->id)) {
            $steps->push(['approver' => $hrd, 'peran' => PeranApproval::Hrd]);
        }

        return self::beriUrutan($steps);
    }

    /** Tulis rantai approval untuk sebuah sanksi (mengganti baris lama bila ada). */
    public static function bangunUntuk(SanksiDisiplin $sanksi): void
    {
        $steps = self::susun($sanksi->pengusul);

        DB::transaction(function () use ($sanksi, $steps) {
            ApprovalSanksi::where('sanksi_id', $sanksi->id)->delete();
            foreach ($steps as $s) {
                ApprovalSanksi::create([
                    'sanksi_id' => $sanksi->id,
                    'urutan' => $s['urutan'],
                    'approver_id' => $s['approver']->id,
                    'peran' => $s['peran'],
                    'status' => StatusApproval::Menunggu,
                ]);
            }
        });
    }

    /** Karyawan (aktif) pemegang sebuah role via akun user. HRD dijamin 1 orang. */
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
