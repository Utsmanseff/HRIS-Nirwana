<?php

namespace App\Livewire\Cuti;

use App\Models\Karyawan;
use App\Support\AturanCuti;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class CutiForm extends Component
{
    use WithFileUploads;

    public string $jenisCutiId = '';

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public int $jumlahHari = 1;

    public string $alasan = '';

    public $lampiran = null; // UploadedFile

    private function karyawan(): Karyawan
    {
        return auth()->user()->karyawan()->firstOrFail();
    }

    public function render()
    {
        return view('livewire.cuti.cuti-form', [
            'jenisOptions' => AturanCuti::jenisTersedia($this->karyawan()),
        ]);
    }
}
