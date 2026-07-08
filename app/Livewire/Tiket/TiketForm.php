<?php

namespace App\Livewire\Tiket;

use App\Enums\JenisTiket;
use App\Enums\PrioritasTiket;
use App\Enums\StatusTiket;
use App\Enums\TimTeknis;
use App\Models\Aset;
use App\Models\Karyawan;
use App\Models\Tiket;
use App\Support\NavMenu;
use App\Support\ProsesTiket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TiketForm extends Component
{
    public bool $adalahTim = false;

    // Field umum
    public string $tim = '';
    public string $jenis = 'perbaikan';
    public string $prioritas = 'sedang';
    public string $judul = '';
    public string $deskripsi = '';

    // Field tim
    public ?int $inventarisId = null;
    public string $asetLabel = '';
    public string $cariAset = '';
    public ?int $pelaporId = null;
    public string $pelaporLabel = '';
    public string $cariPelapor = '';
    public string $waktuLapor = '';
    public string $statusLanjut = 'baru';
    public string $catatanPenyelesaian = '';

    public function mount(): void
    {
        $tims = auth()->user()->timTeknis();
        $this->adalahTim = count($tims) > 0;
        $this->tim = $this->adalahTim ? $tims[0]->value : TimTeknis::It->value;
        $this->waktuLapor = now()->format('Y-m-d\TH:i');
    }

    private function timNilai(): array
    {
        return array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
    }

    /** @return array<int,array{id:int,label:string}> */
    public function getAsetOpsiProperty(): array
    {
        if (! $this->adalahTim || strlen($this->cariAset) < 2) {
            return [];
        }

        return Aset::query()->with('kategori')->tim($this->timNilai())
            ->where(fn ($w) => $w->where('nama', 'like', "%{$this->cariAset}%")->orWhere('kode', 'like', "%{$this->cariAset}%"))
            ->limit(8)->get()
            ->map(fn ($a) => ['id' => $a->id, 'label' => "{$a->nama} ({$a->kode})"])->all();
    }

    public function pilihAset(int $id): void
    {
        $aset = Aset::with('kategori')->tim($this->timNilai())->findOrFail($id);
        $this->inventarisId = $aset->id;
        $this->asetLabel = "{$aset->nama} ({$aset->kode})";
        $this->tim = $aset->kategori->tim->value; // auto + lock
        $this->cariAset = '';
    }

    public function lepasAset(): void
    {
        $this->reset('inventarisId', 'asetLabel', 'cariAset');
    }

    /** @return array<int,array{id:int,label:string}> */
    public function getPelaporOpsiProperty(): array
    {
        if (! $this->adalahTim || strlen($this->cariPelapor) < 2) {
            return [];
        }

        return Karyawan::query()->aktif()
            ->where(fn ($w) => $w->where('nama_lengkap', 'like', "%{$this->cariPelapor}%")->orWhere('nip', 'like', "%{$this->cariPelapor}%"))
            ->limit(8)->get()
            ->map(fn ($k) => ['id' => $k->id, 'label' => "{$k->nama_lengkap} ({$k->nip})"])->all();
    }

    public function pilihPelapor(int $id): void
    {
        $k = Karyawan::findOrFail($id);
        $this->pelaporId = $k->id;
        $this->pelaporLabel = "{$k->nama_lengkap} ({$k->nip})";
        $this->cariPelapor = '';
    }

    public function lepasPelapor(): void
    {
        $this->reset('pelaporId', 'pelaporLabel', 'cariPelapor');
    }

    public function simpan()
    {
        $data = $this->validate([
            'tim' => ['required', 'in:it,sarana,atem'],
            'jenis' => ['required', 'in:perbaikan,pemeliharaan'],
            'prioritas' => ['required', 'in:rendah,sedang,tinggi,urgent'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string'],
        ]);

        $user = auth()->user();

        // Karyawan biasa: paksa pelapor=diri, status baru, tanpa aset.
        if (! $this->adalahTim) {
            $pelaporId = $user->karyawan_id;
            $unitPelapor = $user->karyawan?->orgUnit?->nama;
            $inventarisId = null;
            $jenis = JenisTiket::Perbaikan->value;
            $statusLanjut = StatusTiket::Baru->value;
            $waktuLapor = now();
        } else {
            $pelaporId = $this->pelaporId;
            $unitPelapor = $pelaporId ? Karyawan::find($pelaporId)?->orgUnit?->nama : null;
            $inventarisId = $this->inventarisId;
            $jenis = $data['jenis'];
            $statusLanjut = in_array($this->statusLanjut, ['baru', 'diproses', 'selesai'], true) ? $this->statusLanjut : 'baru';
            $waktuLapor = $this->waktuLapor ? Carbon::parse($this->waktuLapor) : now();
        }

        $tiket = DB::transaction(function () use ($data, $user, $pelaporId, $unitPelapor, $inventarisId, $jenis, $waktuLapor, $statusLanjut) {
            $tiket = Tiket::create([
                'nomor' => Tiket::buatNomor(),
                'jenis' => $jenis,
                'tim' => $data['tim'],
                'inventaris_id' => $inventarisId,
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'],
                'pelapor_id' => $pelaporId,
                'unit_pelapor' => $unitPelapor,
                'dibuat_oleh' => $user->id,
                'prioritas' => $data['prioritas'],
                'status' => StatusTiket::Baru->value,
                'waktu_lapor' => $waktuLapor,
            ]);

            if ($statusLanjut === 'diproses') {
                ProsesTiket::mulai($tiket->fresh(), $user);
            } elseif ($statusLanjut === 'selesai') {
                ProsesTiket::selesai($tiket->fresh(), $user, $this->catatanPenyelesaian ?: null);
            }

            return $tiket->fresh();
        });

        // Notif TiketBaru bila masih di antrian (diisi Task 9).
        if (in_array($tiket->status, StatusTiket::aktif(), true)) {
            // Diisi Task 9: Notification::send(tim, new TiketBaru($tiket)).
        }

        session()->flash('ok', "Tiket {$tiket->nomor} dibuat.");

        return Route::has('tiket.detail')
            ? redirect()->route('tiket.detail', $tiket)
            : redirect()->route('tiket');
    }

    public function render()
    {
        return view('livewire.tiket.tiket-form', [
            'menu' => NavMenu::untuk(auth()->user()),
            'timOpsi' => $this->adalahTim
                ? array_values(array_filter(TimTeknis::cases(), fn ($t) => in_array($t->value, $this->timNilai(), true)))
                : TimTeknis::cases(),
            'jenisOpsi' => JenisTiket::cases(),
            'prioritasOpsi' => PrioritasTiket::cases(),
            'asetOpsi' => $this->asetOpsi,
            'pelaporOpsi' => $this->pelaporOpsi,
        ]);
    }
}
