<?php

namespace App\Livewire\Sdm;

use App\Enums\JenisKontrak;
use App\Enums\StatusKaryawan;
use App\Models\IzinKaryawan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Support\KompresGambar;
use App\Support\PengingatKontrak;
use App\Support\ProsesPengganti;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class KaryawanDetail extends Component
{
    use WithFileUploads;

    public Karyawan $karyawan;

    #[Url]
    public string $tab = 'profil';

    public bool $showFormKontrak = false;

    public string $kJenis = 'pkwt';

    public string $kMulai = '';

    public string $kAkhir = '';

    public string $kKeterangan = '';

    public $berkas = null;

    public string $tipeDokumen = '';

    public bool $showNonaktif = false;

    public string $alasanNonaktif = '';

    public string $tanggalNonaktif = '';

    // Perizinan (STR/SIP/SIK/sertifikat)
    public ?int $izJenisId = null;

    public string $izNomor = '';

    public string $izMulai = '';

    public string $izAkhir = '';

    public function mount(Karyawan $karyawan): void
    {
        $this->karyawan = $karyawan->load([
            'orgUnit.parent', 'jabatan', 'kontrak', 'dokumen', 'user.roles',
            'sanksiDisiplin.pengusul', 'izin.jenis',
        ]);
    }

    public function inisial(): string
    {
        return collect(explode(' ', trim($this->karyawan->nama_lengkap)))
            ->filter()->take(2)
            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode('');
    }

    public function formKontrakBaru(): void
    {
        $this->reset(['kMulai', 'kAkhir', 'kKeterangan']);
        $this->kJenis = 'pkwt';
        $this->showFormKontrak = true;
    }

    public function batalKontrak(): void
    {
        $this->reset(['showFormKontrak', 'kMulai', 'kAkhir', 'kKeterangan']);
    }

    public function simpanKontrak(): void
    {
        $this->validate([
            'kJenis' => ['required', 'in:percobaan_unpaid,percobaan,pkwt,tetap'],
            'kMulai' => ['required', 'date'],
            'kAkhir' => $this->kJenis === 'tetap'
                ? ['nullable']
                : ['required', 'date', 'after:kMulai'],
            'kKeterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->karyawan->kontrak()->create([
            'jenis' => $this->kJenis,
            'tanggal_mulai' => $this->kMulai,
            'tanggal_akhir' => $this->kJenis === 'tetap' || $this->kAkhir === '' ? null : $this->kAkhir,
            'keterangan' => $this->kKeterangan === '' ? null : $this->kKeterangan,
        ]);

        $this->karyawan->load('kontrak')->unsetRelation('kontrakTerbaru');
        $this->batalKontrak();
    }

    public function formNonaktif(): void
    {
        $this->alasanNonaktif = '';
        $this->tanggalNonaktif = now()->format('Y-m-d');
        $this->showNonaktif = true;
    }

    public function batalNonaktif(): void
    {
        $this->reset(['showNonaktif', 'alasanNonaktif', 'tanggalNonaktif']);
    }

    public function nonaktifkan(): void
    {
        $this->validate([
            'alasanNonaktif' => ['required', 'in:resign,kontrak_berakhir,phk,pensiun,meninggal'],
            'tanggalNonaktif' => ['required', 'date'],
        ]);

        $this->karyawan->update([
            'status' => 'nonaktif',
            'alasan_nonaktif' => $this->alasanNonaktif,
            'tanggal_nonaktif' => $this->tanggalNonaktif,
        ]);

        $this->batalNonaktif();
    }

    public function aktifkanLagi(): void
    {
        $this->karyawan->update([
            'status' => 'aktif',
            'alasan_nonaktif' => null,
            'tanggal_nonaktif' => null,
        ]);
    }

    public function unggahDokumen(): void
    {
        $this->validate([
            'tipeDokumen' => ['required', 'in:ktp,ijazah,kontrak,sip,lainnya'],
            'berkas' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $namaAsli = pathinfo($this->berkas->getClientOriginalName(), PATHINFO_FILENAME);
        $dir = 'dokumen/'.$this->karyawan->id;

        if ($this->berkas->getMimeType() === 'application/pdf') {
            $nama = Str::slug($namaAsli).'-'.Str::random(6).'.pdf';
            $path = $this->berkas->storeAs($dir, $nama, 'local');
            $mime = 'application/pdf';
            $ukuran = $this->berkas->getSize();
        } else {
            $webp = KompresGambar::keWebp($this->berkas->get());
            $nama = Str::slug($namaAsli).'-'.Str::random(6).'.webp';
            $path = $dir.'/'.$nama;
            Storage::disk('local')->put($path, $webp);
            $mime = 'image/webp';
            $ukuran = strlen($webp);
        }

        $this->karyawan->dokumen()->create([
            'tipe' => $this->tipeDokumen,
            'path' => $path,
            'mime' => $mime,
            'ukuran' => $ukuran,
        ]);

        $this->karyawan->load('dokumen');
        $this->reset(['berkas', 'tipeDokumen']);
    }

    public function simpanIzin(): void
    {
        $data = $this->validate([
            'izJenisId' => ['required', 'exists:jenis_izin,id'],
            'izNomor' => ['nullable', 'string', 'max:100'],
            'izMulai' => ['nullable', 'date'],
            'izAkhir' => ['required', 'date', 'after:izMulai'],
        ]);

        IzinKaryawan::create([
            'karyawan_id' => $this->karyawan->id,
            'jenis_izin_id' => $data['izJenisId'],
            'nomor' => $data['izNomor'] ?: null,
            'berlaku_mulai' => $data['izMulai'] ?: null,
            'berlaku_akhir' => $data['izAkhir'],
        ]);

        $this->reset(['izJenisId', 'izNomor', 'izMulai', 'izAkhir']);
        $this->karyawan->load('izin.jenis');
        session()->flash('ok', 'Izin ditambahkan.');
    }

    public function hapusIzin(int $id): void
    {
        // Dikunci ke karyawan yang sedang dibuka — id dari klien tak boleh dipercaya.
        IzinKaryawan::where('karyawan_id', $this->karyawan->id)->whereKey($id)->delete();
        $this->karyawan->load('izin.jenis');
    }

    /** Kontrak urut terbaru→terlama untuk timeline. */
    public function riwayatKontrak(): Collection
    {
        return $this->karyawan->kontrak->sortByDesc('tanggal_mulai')->sortByDesc('id')->values();
    }

    /** Id kontrak PKWT pertama (anchor hak cuti tahunan) — null bila belum ada PKWT. */
    public function idAnchorCuti(): ?int
    {
        return $this->karyawan->kontrak
            ->filter(fn ($k) => $k->jenis === JenisKontrak::Pkwt)
            ->sortBy('tanggal_mulai')->sortBy('id')
            ->first()?->id;
    }

    public function pengingatKontrak(): ?PengingatKontrak
    {
        return PengingatKontrak::untuk($this->karyawan);
    }

    public function ukuranBaca(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' MB'
            : number_format(max(1, round($bytes / 1024))).' KB';
    }

    /** Karyawan nonaktif yang jadwalnya belum dibereskan (lowongan masih terbuka). */
    public function lowonganTerbuka(): bool
    {
        if ($this->karyawan->status === StatusKaryawan::Aktif) {
            return false;
        }

        return ProsesPengganti::lowongan([$this->karyawan->org_unit_id])
            ->contains('id', $this->karyawan->id);
    }

    public function render()
    {
        return view('livewire.sdm.karyawan-detail', [
            'jenisIzin' => JenisIzin::aktif()->orderBy('nama')->get(),
        ]);
    }
}
