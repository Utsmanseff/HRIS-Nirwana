<?php

namespace App\Livewire\Cuti;

use App\Models\HariLibur;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PenyesuaianSaldo;
use App\Support\SaldoCuti;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KelolaCuti extends Component
{
    #[Url]
    public string $tab = 'hari-libur';

    public string $hlTanggal = '';

    public string $hlNama = '';

    public ?int $editHlId = null;

    public function simpanHariLibur(): void
    {
        $data = $this->validate([
            'hlTanggal' => ['required', 'date'],
            'hlNama' => ['required', 'string', 'max:120'],
        ]);

        HariLibur::updateOrCreate(
            ['id' => $this->editHlId],
            ['tanggal' => $data['hlTanggal'], 'nama' => $data['hlNama']],
        );
        $this->resetHariLibur();
        session()->flash('cuti_ok', 'Hari libur tersimpan.');
    }

    public function editHariLibur(int $id): void
    {
        $h = HariLibur::findOrFail($id);
        $this->editHlId = $h->id;
        $this->hlTanggal = $h->tanggal->toDateString();
        $this->hlNama = $h->nama;
    }

    public function hapusHariLibur(int $id): void
    {
        HariLibur::whereKey($id)->delete();
        if ($this->editHlId === $id) {
            $this->resetHariLibur();
        }
    }

    public function resetHariLibur(): void
    {
        $this->reset(['hlTanggal', 'hlNama', 'editHlId']);
    }

    public ?int $jcId = null;

    public string $jcNama = '';

    public ?string $jcEfek = null;

    public bool $jcPotongSaldo = false;

    public bool $jcButuhLampiran = false;

    public bool $jcBolehBackdate = false;

    public function editJenis(int $id): void
    {
        $j = JenisCuti::findOrFail($id);
        $this->jcId = $j->id;
        $this->jcNama = $j->nama;
        $this->jcEfek = $j->efek_penggajian;
        $this->jcPotongSaldo = (bool) $j->potong_saldo;
        $this->jcButuhLampiran = (bool) $j->butuh_lampiran;
        $this->jcBolehBackdate = (bool) $j->boleh_backdate;
    }

    public function simpanJenis(): void
    {
        $data = $this->validate([
            'jcNama' => ['required', 'string', 'max:80'],
            'jcEfek' => ['nullable', 'string', 'max:80'],
        ]);

        JenisCuti::whereKey($this->jcId)->update([
            'nama' => $data['jcNama'],
            'efek_penggajian' => $data['jcEfek'] ?: null,
            'potong_saldo' => $this->jcPotongSaldo,
            'butuh_lampiran' => $this->jcButuhLampiran,
            'boleh_backdate' => $this->jcBolehBackdate,
        ]);
        $this->reset(['jcId', 'jcNama', 'jcEfek', 'jcPotongSaldo', 'jcButuhLampiran', 'jcBolehBackdate']);
        session()->flash('cuti_ok', 'Jenis cuti diperbarui.');
    }

    public function toggleAktif(int $id): void
    {
        $j = JenisCuti::findOrFail($id);
        $j->update(['aktif' => ! $j->aktif]);
    }

    public string $psCari = '';

    public ?int $psKaryawanId = null;

    public string $psPeriode = '';

    public ?int $psDelta = null;

    public string $psAlasan = '';

    public function pilihKaryawan(int $id): void
    {
        $this->psKaryawanId = $id;
        $this->psCari = '';
        $this->reset(['psPeriode', 'psDelta', 'psAlasan']);
        $this->resetErrorBag();
    }

    public function batalKaryawan(): void
    {
        $this->reset(['psKaryawanId', 'psCari', 'psPeriode', 'psDelta', 'psAlasan']);
    }

    /** @return list<string> periode_mulai valid (Y-m-d) untuk karyawan terpilih. */
    private function periodeValidStr(): array
    {
        if (! $this->psKaryawanId) {
            return [];
        }
        $kar = Karyawan::find($this->psKaryawanId);

        return $kar
            ? array_map(fn ($c) => $c->toDateString(), SaldoCuti::untuk($kar)->periodeValid())
            : [];
    }

    public function simpanPenyesuaian(): void
    {
        $this->validate([
            'psKaryawanId' => ['required', 'exists:karyawan,id'],
            'psPeriode' => ['required', 'date'],
            'psDelta' => ['required', 'integer', 'not_in:0', 'between:-30,30'],
            'psAlasan' => ['required', 'string', 'max:255'],
        ]);

        if (! in_array($this->psPeriode, $this->periodeValidStr(), true)) {
            $this->addError('psPeriode', 'Periode tak valid untuk karyawan ini.');

            return;
        }

        PenyesuaianSaldo::create([
            'karyawan_id' => $this->psKaryawanId,
            'periode_mulai' => $this->psPeriode,
            'delta' => $this->psDelta,
            'alasan' => $this->psAlasan,
            'dibuat_oleh' => auth()->id(),
        ]);
        $this->reset(['psPeriode', 'psDelta', 'psAlasan']);
        session()->flash('cuti_ok', 'Penyesuaian tersimpan.');
    }

    public function hapusPenyesuaian(int $id): void
    {
        PenyesuaianSaldo::whereKey($id)->delete();
    }

    public function render()
    {
        return view('livewire.cuti.kelola-cuti', [
            'hariLibur' => HariLibur::orderBy('tanggal')->get(),
            'jenisCuti' => JenisCuti::orderBy('id')->get(),
            'hasilCari' => ($this->tab === 'penyesuaian' && trim($this->psCari) !== '')
                ? Karyawan::aktif()
                    ->where(fn ($q) => $q->where('nama_lengkap', 'like', '%'.trim($this->psCari).'%')
                        ->orWhere('nip', 'like', '%'.trim($this->psCari).'%'))
                    ->limit(8)->get()
                : collect(),
            'karyawanTerpilih' => $this->psKaryawanId ? Karyawan::find($this->psKaryawanId) : null,
            'periodeOpsi' => $this->periodeValidStr(),
            'penyesuaian' => $this->psKaryawanId
                ? PenyesuaianSaldo::where('karyawan_id', $this->psKaryawanId)->with('pembuat')->orderByDesc('periode_mulai')->get()
                : collect(),
        ]);
    }
}
