<?php

namespace App\Livewire\Tiket;

use App\Enums\Role;
use App\Enums\StatusAset;
use App\Enums\StatusTiket;
use App\Models\Aset;
use App\Models\LampiranTiket;
use App\Models\Tiket;
use App\Support\KompresGambar;
use App\Support\NavMenu;
use App\Support\ProsesTiket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class TiketDetail extends Component
{
    use WithFileUploads;

    public Tiket $tiket;
    public string $catatanSelesai = '';
    public $berkas = null;

    // Taut aset (tim, saat tiket belum tertaut)
    public string $cariAset = '';

    // Edit waktu respon (tim, koreksi bila kerja duluan lupa klik proses)
    public bool $editRespon = false;
    public string $waktuResponInput = '';

    public function mount(Tiket $tiket): void
    {
        abort_unless($this->boleh($tiket), 403);
        $this->tiket = $tiket;
    }

    private function timUser(): array
    {
        return array_map(fn ($t) => $t->value, auth()->user()->timTeknis());
    }

    private function boleh(Tiket $tiket): bool
    {
        $user = auth()->user();
        if ($user->hasRole(Role::AdminSistem->value)) {
            return true;
        }
        if (in_array($tiket->tim->value, $this->timUser(), true)) {
            return true;
        }

        return $tiket->pelapor_id && $tiket->pelapor_id === $user->karyawan_id;
    }

    private function anggotaTim(): bool
    {
        return in_array($this->tiket->tim->value, $this->timUser(), true)
            || auth()->user()->hasRole(Role::AdminSistem->value);
    }

    public function mulai(): void
    {
        abort_unless($this->anggotaTim(), 403);
        ProsesTiket::mulai($this->tiket, auth()->user());
        $this->tiket->refresh();
    }

    public function selesaikan(): void
    {
        abort_unless($this->anggotaTim(), 403);
        ProsesTiket::selesai($this->tiket, auth()->user(), $this->catatanSelesai ?: null);
        $this->tiket->refresh();
        $this->reset('catatanSelesai');
    }

    public function batalkan(): void
    {
        abort_unless($this->anggotaTim(), 403);
        ProsesTiket::batal($this->tiket);
        $this->tiket->refresh();
    }

    /** @return array<int,array{id:int,label:string}> aset se-tim yang bisa ditaut. */
    public function getAsetOpsiProperty(): array
    {
        if (! $this->anggotaTim() || strlen($this->cariAset) < 2) {
            return [];
        }

        return Aset::query()->with('kategori')->tim([$this->tiket->tim->value])
            ->where(fn ($w) => $w->where('nama', 'like', "%{$this->cariAset}%")->orWhere('kode', 'like', "%{$this->cariAset}%"))
            ->limit(8)->get()
            ->map(fn ($a) => ['id' => $a->id, 'label' => "{$a->nama} ({$a->kode})"])->all();
    }

    /** Tautkan aset ke tiket yang belum tertaut (aset wajib se-tim). */
    public function tautAset(int $id): void
    {
        abort_unless($this->anggotaTim(), 403);
        abort_if($this->tiket->inventaris_id !== null, 422);

        $aset = Aset::with('kategori')->tim([$this->tiket->tim->value])->findOrFail($id);
        $this->tiket->update(['inventaris_id' => $aset->id]);

        // Bila perbaikan & tiket masih aktif, sinkronkan status aset.
        if (in_array($this->tiket->status, StatusTiket::aktif(), true)) {
            ProsesTiket::asetDalamPerbaikan($this->tiket->fresh());
        }

        $this->reset('cariAset');
        $this->tiket->refresh();
    }

    /** Lepas taut aset; kembalikan status aset (dalam_perbaikan → baik) bila perlu. */
    public function lepasAsetTaut(): void
    {
        abort_unless($this->anggotaTim(), 403);
        $aset = $this->tiket->aset;
        if ($aset && $aset->status === StatusAset::DalamPerbaikan) {
            $aset->update(['status' => StatusAset::Baik->value]);
        }
        $this->tiket->update(['inventaris_id' => null]);
        $this->tiket->refresh();
    }

    public function mulaiEditRespon(): void
    {
        abort_unless($this->anggotaTim(), 403);
        $this->editRespon = true;
        $this->waktuResponInput = $this->tiket->waktu_respon?->format('Y-m-d\TH:i')
            ?? $this->tiket->waktu_lapor->format('Y-m-d\TH:i');
    }

    /** Koreksi waktu respon (kasus: kerja duluan, lupa klik proses di app). */
    public function simpanWaktuRespon(): void
    {
        abort_unless($this->anggotaTim(), 403);
        $this->validate(['waktuResponInput' => ['required', 'date']]);

        $respon = Carbon::parse($this->waktuResponInput);
        if ($respon->lt($this->tiket->waktu_lapor)) {
            $this->addError('waktuResponInput', 'Waktu respon tidak boleh sebelum waktu lapor.');

            return;
        }
        if ($this->tiket->waktu_selesai && $respon->gt($this->tiket->waktu_selesai)) {
            $this->addError('waktuResponInput', 'Waktu respon tidak boleh setelah waktu selesai.');

            return;
        }

        $this->tiket->update(['waktu_respon' => $respon]);
        $this->editRespon = false;
        $this->tiket->refresh();
    }

    public function batalEditRespon(): void
    {
        $this->reset('editRespon', 'waktuResponInput');
    }

    public function simpanLampiran(): void
    {
        abort_unless($this->anggotaTim() || $this->tiket->pelapor_id === auth()->user()->karyawan_id, 403);
        $this->validate(['berkas' => ['required', 'file', 'max:8192', 'mimes:jpg,jpeg,png,webp,pdf']]);

        $isPdf = strtolower($this->berkas->getClientOriginalExtension()) === 'pdf'
            || $this->berkas->getMimeType() === 'application/pdf';
        $dir = 'tiket/'.$this->tiket->id;

        if ($isPdf) {
            $path = $this->berkas->store($dir, 'local');
            $mime = 'application/pdf';
        } else {
            $webp = KompresGambar::keWebp(file_get_contents($this->berkas->getRealPath()), 70, 1600);
            $path = $dir.'/'.Str::uuid().'.webp';
            Storage::disk('local')->put($path, $webp);
            $mime = 'image/webp';
        }

        LampiranTiket::create(['tiket_id' => $this->tiket->id, 'path' => $path, 'mime' => $mime]);
        $this->reset('berkas');
        $this->tiket->refresh();
    }

    public function render()
    {
        $this->tiket->load(['aset.kategori', 'pelapor', 'dibuatOleh', 'penyelesai', 'lampiran']);

        return view('livewire.tiket.tiket-detail', [
            'menu' => NavMenu::untuk(auth()->user()),
            'anggotaTim' => $this->anggotaTim(),
            'asetOpsi' => $this->asetOpsi,
        ]);
    }
}
