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
     * Susun rantai approver dari PENGUSUL naik: ...→HRD→Direktur (final).
     * Direktur/HRD pengusul → rantai pendek (Direktur self-terbit; HRD→[Direktur]).
     *
     * @return Collection<int, array{urutan:int, approver:Karyawan, peran:PeranApproval}>
     */
    public static function susun(Karyawan $pengusul): Collection
    {
        $lvl = $pengusul->jabatan?->level?->value ?? 0;

        // Direktur buat-langsung → dirinya sendiri tahap final (terbit langsung).
        if ($lvl >= JabatanLevel::Direktur->value) {
            return self::beriUrutan(collect([
                ['approver' => $pengusul, 'peran' => PeranApproval::Direktur],
            ]));
        }

        $steps = collect();

        // HRD buat-langsung → tanpa approver unit; langsung ke Direktur.
        if (! $pengusul->user?->hasRole(Role::Hrd->value)) {
            // Pengusul di bawah Kabid → naik sampai Kabid (inklusif).
            if ($lvl < JabatanLevel::Kabid->value) {
                $current = $pengusul->atasanDerived();
                while ($current) {
                    $clvl = $current->jabatan?->level?->value ?? 0;
                    if ($clvl >= JabatanLevel::Direktur->value) {
                        break; // Direktur tak jadi approver via jalur unit
                    }
                    $steps->push([
                        'approver' => $current,
                        'peran' => $clvl >= JabatanLevel::Kabid->value ? PeranApproval::Kabid : PeranApproval::Koordinator,
                    ]);
                    if ($clvl >= JabatanLevel::Kabid->value) {
                        break;
                    }
                    $current = $current->atasanDerived();
                }
            }

            // Append HRD (antara).
            $hrd = self::pemegangRole(Role::Hrd);
            if ($hrd && ! $steps->contains(fn ($s) => $s['approver']->id === $hrd->id)) {
                $steps->push(['approver' => $hrd, 'peran' => PeranApproval::Hrd]);
            }
        }

        // Append Direktur final.
        $direktur = self::pemegangRole(Role::Direktur);
        if ($direktur && ! $steps->contains(fn ($s) => $s['approver']->id === $direktur->id)) {
            $steps->push(['approver' => $direktur, 'peran' => PeranApproval::Direktur]);
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
