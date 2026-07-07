<?php

namespace App\Livewire\Disiplin;

use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\SanksiDisiplin;
use App\Support\EskalasiSanksi;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KelolaDisiplin extends Component
{
    #[Url]
    public string $cari = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterTingkat = '';

    public bool $showForm = false;

    public string $cariKaryawan = '';

    public ?int $karyawanId = null;

    public string $tingkat = '';

    public string $uraian = '';

    public string $tanggalKejadian = '';

    public string $nomorSurat = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('buat-sanksi'), 403);
    }

    public function bukaForm(): void
    {
        $this->showForm = true;
        $this->reset(['cariKaryawan', 'karyawanId', 'tingkat', 'uraian', 'tanggalKejadian', 'nomorSurat']);
        $this->resetErrorBag();
    }

    public function tutupForm(): void
    {
        $this->reset(['showForm', 'cariKaryawan', 'karyawanId', 'tingkat', 'uraian', 'tanggalKejadian', 'nomorSurat']);
        $this->resetErrorBag();
    }

    public function pilihKaryawan(int $id): void
    {
        $kar = Karyawan::aktif()->whereKey($id)->first();
        if (! $kar) {
            return;
        }
        $this->karyawanId = $kar->id;
        $this->cariKaryawan = '';
        $this->tingkat = (string) EskalasiSanksi::sarankan($kar)->value;
        $this->resetErrorBag();
    }

    public function batalKaryawan(): void
    {
        $this->reset(['karyawanId', 'cariKaryawan', 'tingkat']);
    }

    protected function daftar()
    {
        return SanksiDisiplin::query()
            ->when($this->cari !== '', fn ($q) => $q->whereHas('karyawan', fn ($k) => $k
                ->where('nama_lengkap', 'like', "%{$this->cari}%")
                ->orWhere('nip', 'like', "%{$this->cari}%")))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterTingkat !== '', fn ($q) => $q->where('tingkat', $this->filterTingkat))
            ->with(['karyawan.jabatan', 'pengusul'])
            ->latest()
            ->limit(100)
            ->get();
    }

    public function render()
    {
        return view('livewire.disiplin.kelola-disiplin', [
            'daftar' => $this->daftar(),
            'statusOpsi' => StatusSanksi::cases(),
            'tingkatOpsi' => TingkatSanksi::cases(),
            'bisaCabut' => auth()->user()->can('kelola-disiplin'),
            'hasilCari' => ($this->showForm && ! $this->karyawanId && trim($this->cariKaryawan) !== '')
                ? Karyawan::aktif()
                    ->where(fn ($q) => $q->where('nama_lengkap', 'like', '%'.trim($this->cariKaryawan).'%')
                        ->orWhere('nip', 'like', '%'.trim($this->cariKaryawan).'%'))
                    ->limit(8)->get()
                : collect(),
            'karyawanTerpilih' => $this->karyawanId ? Karyawan::find($this->karyawanId) : null,
            'sanksiAktif' => $this->karyawanId ? EskalasiSanksi::sanksiAktif(Karyawan::find($this->karyawanId)) : collect(),
        ]);
    }
}
