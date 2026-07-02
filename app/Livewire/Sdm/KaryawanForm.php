<?php

namespace App\Livewire\Sdm;

use App\Enums\JenisKontrak;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KaryawanForm extends Component
{
    // Identitas
    public string $nip = '';

    public string $namaLengkap = '';

    // Data pribadi
    public string $nik = '';

    public string $tempatLahir = '';

    public string $tanggalLahir = '';

    public string $jenisKelamin = '';

    public string $agama = '';

    public string $statusNikah = '';

    public string $pendidikan = '';

    // SIP (nakes, opsional)
    public string $sipNomor = '';

    public string $sipMulai = '';

    public string $sipAkhir = '';

    // Kontak
    public string $noHp = '';

    public string $email = '';

    public string $alamat = '';

    // Penempatan
    public string $orgUnitId = '';

    public string $jabatanId = '';

    public string $atasanId = '';

    public string $tanggalMasuk = '';

    // Kontrak tahap awal (hanya saat tambah)
    public string $jenisKontrak = 'percobaan_unpaid';

    public string $kontrakMulai = '';

    public string $kontrakAkhir = '';

    public string $kontrakKeterangan = '';

    protected function aturan(): array
    {
        return [
            'nip' => ['required', 'string', 'max:50', 'unique:karyawan,nip'],
            'namaLengkap' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'string', 'max:20'],
            'tempatLahir' => ['nullable', 'string', 'max:100'],
            'tanggalLahir' => ['nullable', 'date'],
            'jenisKelamin' => ['nullable', 'in:L,P'],
            'agama' => ['nullable', 'string', 'max:30'],
            'statusNikah' => ['nullable', 'in:belum,menikah,cerai'],
            'pendidikan' => ['nullable', 'string', 'max:100'],
            'sipNomor' => ['nullable', 'string', 'max:100'],
            'sipMulai' => ['nullable', 'date', 'required_with:sipNomor'],
            'sipAkhir' => ['nullable', 'date', 'required_with:sipNomor', 'after:sipMulai'],
            'noHp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'orgUnitId' => ['required', 'exists:org_units,id'],
            'jabatanId' => ['required', 'exists:jabatan,id'],
            'atasanId' => ['nullable', 'exists:karyawan,id'],
            'tanggalMasuk' => ['required', 'date'],
            'jenisKontrak' => ['required', 'in:percobaan_unpaid,percobaan,pkwt,tetap'],
            'kontrakMulai' => ['required', 'date'],
            'kontrakAkhir' => $this->jenisKontrak === 'tetap'
                ? ['nullable']
                : ['required', 'date', 'after:kontrakMulai'],
            'kontrakKeterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Nilai kolom karyawan dari properti form ('' → null untuk kolom nullable). */
    protected function nilaiKaryawan(): array
    {
        $opsional = fn (string $v) => $v === '' ? null : $v;

        return [
            'nip' => $this->nip,
            'nama_lengkap' => $this->namaLengkap,
            'nik' => $opsional($this->nik),
            'tempat_lahir' => $opsional($this->tempatLahir),
            'tanggal_lahir' => $opsional($this->tanggalLahir),
            'jenis_kelamin' => $opsional($this->jenisKelamin),
            'agama' => $opsional($this->agama),
            'status_nikah' => $opsional($this->statusNikah),
            'pendidikan_terakhir' => $opsional($this->pendidikan),
            'sip_nomor' => $opsional($this->sipNomor),
            'sip_berlaku_mulai' => $opsional($this->sipMulai),
            'sip_berlaku_akhir' => $opsional($this->sipAkhir),
            'no_hp' => $opsional($this->noHp),
            'email' => $opsional($this->email),
            'alamat' => $opsional($this->alamat),
            'org_unit_id' => (int) $this->orgUnitId,
            'jabatan_id' => (int) $this->jabatanId,
            'atasan_id' => $this->atasanId === '' ? null : (int) $this->atasanId,
            'tanggal_masuk' => $this->tanggalMasuk,
        ];
    }

    public function simpan()
    {
        $this->validate($this->aturan());

        $karyawan = DB::transaction(function () {
            $karyawan = Karyawan::create($this->nilaiKaryawan() + ['status' => 'aktif']);

            $karyawan->kontrak()->create([
                'jenis' => $this->jenisKontrak,
                'tanggal_mulai' => $this->kontrakMulai,
                'tanggal_akhir' => $this->jenisKontrak === 'tetap' || $this->kontrakAkhir === ''
                    ? null : $this->kontrakAkhir,
                'keterangan' => $this->kontrakKeterangan === '' ? null : $this->kontrakKeterangan,
            ]);

            return $karyawan;
        });

        return $this->redirectRoute('sdm.karyawan.detail', $karyawan);
    }

    public function render()
    {
        return view('livewire.sdm.karyawan-form', [
            'unitOptions' => OrgUnit::orderBy('nama')->get(['id', 'nama']),
            'jabatanOptions' => Jabatan::orderBy('level')->orderBy('nama')->get(['id', 'nama', 'level']),
            'atasanOptions' => Karyawan::aktif()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']),
            'kontrakOptions' => JenisKontrak::cases(),
        ]);
    }
}
