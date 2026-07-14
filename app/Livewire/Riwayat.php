<?php

namespace App\Livewire;

use App\Support\RiwayatAktivitas;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Riwayat')]
class Riwayat extends Component
{
    use WithPagination;

    #[Url]
    public string $jenis = '';

    public function pilihJenis(string $jenis): void
    {
        $this->jenis = $this->jenis === $jenis ? '' : $jenis;
        $this->resetPage();
    }

    public function render()
    {
        $kar = auth()->user()->karyawan()->first();
        abort_unless($kar, 403);

        $semua = RiwayatAktivitas::untuk($kar, $this->jenis ?: null);

        $perPage = 20;
        $page = $this->getPage();
        $daftar = new LengthAwarePaginator(
            $semua->forPage($page, $perPage)->values(),
            $semua->count(),
            $perPage,
            $page,
            ['path' => Request::url()],
        );

        return view('livewire.riwayat', [
            'daftar' => $daftar,
            'jenisAktif' => $this->jenis,
        ]);
    }
}
