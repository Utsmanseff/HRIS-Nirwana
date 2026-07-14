<?php

namespace App\Livewire;

use App\Enums\JenisKontrak;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Support\NavMenu;
use App\Support\PengingatKontrak;
use App\Support\PengingatSip;
use App\Support\RekapCuti;
use App\Support\SaldoCuti;
use Livewire\Component;

class Beranda extends Component
{
    public function render()
    {
        $user = auth()->user();
        $bisaSdm = $user->can('kelola-sdm');
        $data = ['bisaSdm' => $bisaSdm, 'menu' => NavMenu::untuk($user)];

        if ($bisaSdm) {
            $pengingatKontrak = PengingatKontrak::semua()->sortBy('sisaHari')->values();
            $pengingatSip = PengingatSip::semua()->sortBy('sisaHari')->values();
            $data += [
                'jumlahAktif' => Karyawan::where('status', StatusKaryawan::Aktif->value)->count(),
                'jumlahAkanBerakhir' => $pengingatKontrak->where('sisaHari', '>=', 0)->count(),
                'jumlahTerlewat' => $pengingatKontrak->where('sisaHari', '<', 0)->count(),
                'jumlahBelumTetap' => Karyawan::where('status', StatusKaryawan::Aktif->value)
                    ->whereHas('kontrakTerbaru', fn ($q) => $q->where('jenis', '!=', JenisKontrak::Tetap->value))
                    ->count(),
                'pengingatKontrak' => $pengingatKontrak->take(8),
                'pengingatSip' => $pengingatSip->take(5),
                'totalPerhatian' => $pengingatKontrak->count() + $pengingatSip->count(),
            ];
        }

        // Kartu jatah cuti untuk siapa pun yang punya data karyawan.
        $kar = $user->karyawan()->first();
        $data['saldo'] = $kar ? SaldoCuti::untuk($kar) : null;

        // Kartu sanksi aktif untuk karyawan (muncul saat ada yang aktif).
        $data['sanksiAktif'] = $kar ? \App\Support\EskalasiSanksi::sanksiAktif($kar)->count() : 0;

        // Kartu pending cuti org-wide untuk HRD.
        $data['bisaKelolaCuti'] = $user->can('kelola-cuti');
        if ($data['bisaKelolaCuti']) {
            $data['cutiPending'] = RekapCuti::jumlahPendingOrgWide();
        }

        // Kartu disiplin org-wide untuk HRD.
        $data['bisaKelolaDisiplin'] = $user->can('kelola-disiplin');
        if ($data['bisaKelolaDisiplin']) {
            $data['disiplinPending'] = \App\Support\RekapDisiplin::jumlahPendingOrgWide();
            $data['disiplinDiterbitkan'] = \App\Support\RekapDisiplin::jumlahDiterbitkanOrgWide();
        }

        // Kartu inventaris untuk tim teknis.
        $data['bisaInventaris'] = $user->can('kelola-inventaris');
        if ($data['bisaInventaris']) {
            $timNilai = array_map(fn ($t) => $t->value, $user->timTeknis());
            $data['asetJatuhTempo'] = \App\Support\RekapInventaris::jumlahJatuhTempo($timNilai);
        }

        // Kartu tiket untuk tim teknis / karyawan.
        $data['bisaKerjakanTiket'] = $user->can('kerjakan-tiket');
        if ($data['bisaKerjakanTiket']) {
            $timNilai = array_map(fn ($t) => $t->value, $user->timTeknis());
            $data['tiketAntrian'] = \App\Support\RekapTiket::jumlahAntrian($timNilai);
            $data['tiketTimLabel'] = implode('/', array_map(fn ($t) => $t->label(), $user->timTeknis()));
        }
        $data['tiketSaya'] = $kar ? \App\Support\RekapTiket::jumlahTiketSaya($kar->id) : 0;

        // Kartu absensi untuk siapa pun yang punya karyawan.
        $data['bisaAbsen'] = $kar !== null;
        $data['shiftHariIni'] = null;
        if ($kar) {
            $sesi = \App\Support\ProsesAbsen::sesiAktif($kar);
            $data['absenSesiAktif'] = $sesi !== null;
            $data['absenAksi'] = $sesi ? 'Absen Pulang' : 'Absen Masuk';

            // Shift terjadwal hari ini (bila unit memakai shift) → ditempel di kartu absensi.
            $data['shiftHariIni'] = \App\Models\Jadwal::where('karyawan_id', $kar->id)
                ->whereDate('tanggal', today())
                ->with('shift')
                ->first()?->shift;
        }

        // brand=true → appbar mobile tampil logo + "NirwanaHRIS" (home only).
        return view('livewire.beranda', $data)
            ->layout('components.layouts.app', ['brand' => true]);
    }
}
