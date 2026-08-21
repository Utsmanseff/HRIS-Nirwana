<?php

namespace App\Support;

use App\Enums\JabatanLevel;
use App\Enums\PeranApproval;
use App\Enums\Role;
use App\Enums\RutePengawas;
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
     * $terdakwa hanya dipakai bila pengusul memegang jabatan pengawas — jabatan yang
     * boleh mengusulkan SP untuk seluruh karyawan, sehingga rantainya tak bisa lagi
     * dihitung dari garis komando pengusul sendiri. Lihat RutePengawas.
     *
     * @return Collection<int, array{urutan:int, approver:Karyawan, peran:PeranApproval}>
     */
    public static function susun(Karyawan $pengusul, ?Karyawan $terdakwa = null): Collection
    {
        $lvl = $pengusul->jabatan?->level?->value ?? 0;

        // Direktur buat-langsung → dirinya sendiri tahap final (terbit langsung).
        if ($lvl >= JabatanLevel::Direktur->value) {
            return self::beriUrutan(collect([
                ['approver' => $pengusul, 'peran' => PeranApproval::Direktur],
            ]));
        }

        $steps = collect();
        $rute = $pengusul->jabatan?->rute_pengawas;

        // HRD buat-langsung → tanpa approver unit; langsung ke Direktur.
        if (! $pengusul->user?->hasRole(Role::Hrd->value)) {
            if ($rute === RutePengawas::LangsungHrd) {
                // Pola SPI: sengaja tanpa jalur unit sama sekali, berapa pun levelnya.
            } elseif ($rute === RutePengawas::LewatAtasan && $terdakwa) {
                // Pola supervisor: usulan masuk lewat garis komando karyawan yang
                // diusulkan, bukan garis komando pengusul. Pengusul dilewati bila dia
                // sendiri kebetulan atasan si terdakwa — tak boleh jadi penyetuju
                // usulannya sendiri.
                $steps = self::jalurUnit($terdakwa->atasanDerived(), $pengusul);
            } elseif ($lvl < JabatanLevel::Kabid->value) {
                // Pengusul di bawah Kabid → naik sampai Kabid (inklusif).
                $steps = self::jalurUnit($pengusul->atasanDerived());
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

    /**
     * Naik dari $mulai sampai Kabid (inklusif); Direktur tak ikut lewat jalur unit.
     * $lewati dikeluarkan dari rantai tapi penelusurannya tetap lanjut ke atasannya.
     *
     * @return Collection<int, array{approver:Karyawan, peran:PeranApproval}>
     */
    private static function jalurUnit(?Karyawan $mulai, ?Karyawan $lewati = null): Collection
    {
        $steps = collect();
        $current = $mulai;

        while ($current) {
            $clvl = $current->jabatan?->level?->value ?? 0;
            if ($clvl >= JabatanLevel::Direktur->value) {
                break;
            }

            if (! $lewati || $current->id !== $lewati->id) {
                $steps->push([
                    'approver' => $current,
                    'peran' => $clvl >= JabatanLevel::Kabid->value ? PeranApproval::Kabid : PeranApproval::Koordinator,
                ]);
                if ($clvl >= JabatanLevel::Kabid->value) {
                    break;
                }
            }

            $current = $current->atasanDerived();
        }

        return $steps;
    }

    /** Tulis rantai approval untuk sebuah sanksi (mengganti baris lama bila ada). */
    public static function bangunUntuk(SanksiDisiplin $sanksi): void
    {
        $steps = self::susun($sanksi->pengusul, $sanksi->karyawan);

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
