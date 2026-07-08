<?php

namespace App\Livewire\Tiket;

use App\Enums\Role;
use App\Models\LampiranTiket;
use App\Models\Tiket;
use App\Support\KompresGambar;
use App\Support\NavMenu;
use App\Support\ProsesTiket;
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
        ]);
    }
}
