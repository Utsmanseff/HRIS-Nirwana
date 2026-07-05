<?php

namespace App\Livewire\Cuti;

use App\Enums\StatusPengajuanCuti;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Support\AturanCuti;
use App\Support\KompresGambar;
use App\Support\RantaiApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function simpan()
    {
        // Validasi dasar Livewire dulu (tipe/format).
        $this->validate([
            'jenisCutiId' => ['required', 'exists:jenis_cuti,id'],
            'tanggalMulai' => ['required', 'date'],
            'tanggalSelesai' => ['required', 'date'],
            'jumlahHari' => ['required', 'integer', 'min:1'],
            'alasan' => ['nullable', 'string', 'max:1000'],
            'lampiran' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $kar = $this->karyawan();
        $jenis = JenisCuti::findOrFail($this->jenisCutiId);

        // Guardrail domain (§6/§A2/§A4).
        $err = AturanCuti::periksa(
            $kar, $jenis, $this->tanggalMulai, $this->tanggalSelesai, $this->jumlahHari,
            adaLampiran: $this->lampiran !== null,
        );
        foreach ($err as $field => $pesan) {
            $this->addError($field, $pesan);
        }
        if ($err) {
            return null;
        }

        DB::transaction(function () use ($kar, $jenis) {
            $lampiranPath = null;
            if ($this->lampiran) {
                $dir = 'cuti/'.$kar->id;
                $asli = Str::slug(pathinfo($this->lampiran->getClientOriginalName(), PATHINFO_FILENAME));
                if ($this->lampiran->getMimeType() === 'application/pdf') {
                    $lampiranPath = $this->lampiran->storeAs($dir, $asli.'-'.Str::random(6).'.pdf', 'local');
                } else {
                    $webp = KompresGambar::keWebp($this->lampiran->get());
                    $lampiranPath = $dir.'/'.$asli.'-'.Str::random(6).'.webp';
                    Storage::disk('local')->put($lampiranPath, $webp);
                }
            }

            $pengajuan = PengajuanCuti::create([
                'karyawan_id' => $kar->id,
                'jenis_cuti_id' => $jenis->id,
                'tanggal_mulai' => $this->tanggalMulai,
                'tanggal_selesai' => $this->tanggalSelesai,
                'jumlah_hari' => $this->jumlahHari,
                'alasan' => $this->alasan === '' ? null : $this->alasan,
                'lampiran_path' => $lampiranPath,
                'status' => StatusPengajuanCuti::Diajukan,
            ]);

            // Bangun rantai approval berjenjang (notif dikirim di plan 2-3).
            RantaiApproval::bangunUntuk($pengajuan);
        });

        session()->flash('cuti_ok', 'Pengajuan cuti terkirim.');

        return $this->redirectRoute('cuti');
    }

    public function render()
    {
        $kar = $this->karyawan();
        $opsi = AturanCuti::jenisTersedia($kar);

        return view('livewire.cuti.cuti-form', [
            'jenisOptions' => $opsi,
            'jenisTerpilih' => $this->jenisCutiId !== '' ? $opsi->firstWhere('id', (int) $this->jenisCutiId) : null,
            'rantai' => RantaiApproval::susun($kar),
        ]);
    }
}
