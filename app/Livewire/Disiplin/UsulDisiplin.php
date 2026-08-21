<?php

namespace App\Livewire\Disiplin;

use App\Enums\JabatanLevel;
use App\Enums\StatusKaryawan;
use App\Enums\StatusSanksi;
use App\Enums\TingkatSanksi;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use App\Models\SanksiDisiplin;
use App\Notifications\SanksiPerluPersetujuan;
use App\Support\EskalasiSanksi;
use App\Support\RantaiSanksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class UsulDisiplin extends Component
{
    #[Url]
    public string $cari = '';

    public ?int $karyawanId = null;

    public string $tingkat = '';

    public string $uraian = '';

    public string $tanggalKejadian = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('usul-disiplin'), 403);
    }

    protected function pengusul(): Karyawan
    {
        return auth()->user()->karyawan()->firstOrFail();
    }

    /**
     * Karyawan yang boleh diusulkan.
     *
     * Pemegang jabatan pengawas menjangkau SELURUH karyawan aktif — termasuk sesama
     * pengawas — kecuali dirinya sendiri dan Direktur (pemutus akhir rantai; janggal
     * bila jadi terusul). Selain itu: hanya turunan unitnya sendiri, seperti semula.
     */
    protected function bawahanQuery()
    {
        $pengusul = $this->pengusul();

        $q = Karyawan::query()
            ->where('status', StatusKaryawan::Aktif->value)
            ->where('id', '!=', $pengusul->id);

        if ($pengusul->jabatan?->adalahPengawas()) {
            return $q->whereDoesntHave('jabatan', fn ($j) => $j->where('level', '>=', JabatanLevel::Direktur->value));
        }

        if (! $pengusul->org_unit_id) {
            return Karyawan::query()->whereRaw('1 = 0');
        }

        return $q->whereIn('org_unit_id', OrgUnit::denganTurunan($pengusul->org_unit_id));
    }

    public function pilihKaryawan(int $id): void
    {
        if (! $this->bawahanQuery()->whereKey($id)->exists()) {
            return;
        }
        $this->karyawanId = $id;
        $this->cari = '';
        $kena = Karyawan::find($id);
        $this->tingkat = (string) EskalasiSanksi::sarankan($kena)->value;
        $this->resetErrorBag();
    }

    public function batalKaryawan(): void
    {
        $this->reset(['karyawanId', 'cari', 'tingkat']);
    }

    public function simpan()
    {
        $this->validate([
            'karyawanId' => ['required', 'integer'],
            'uraian' => ['required', 'string', 'max:2000'],
            'tanggalKejadian' => ['required', 'date', 'before_or_equal:today'],
            'tingkat' => ['required', 'integer', 'in:1,2,3,4,5,6'],
        ]);

        if (! $this->bawahanQuery()->whereKey($this->karyawanId)->exists()) {
            $this->addError('karyawanId', 'Karyawan di luar jangkauan usulan Anda.');

            return null;
        }

        $pengusul = $this->pengusul();
        $terdakwa = Karyawan::findOrFail($this->karyawanId);
        $tingkat = TingkatSanksi::from((int) $this->tingkat);

        // Rantai kosong terjadi bila belum ada pemegang role HRD & Direktur. Tanpa
        // penjagaan ini usulan tetap tersimpan tapi nol penyetuju dan nol notifikasi —
        // buntu tanpa tanda apa pun.
        if (RantaiSanksi::susun($pengusul, $terdakwa)->isEmpty()) {
            $this->addError('karyawanId', 'Belum ada pemegang peran HRD/Direktur, usulan tak punya penyetuju. Hubungi Admin Sistem.');

            return null;
        }

        DB::transaction(function () use ($pengusul, $tingkat) {
            $sanksi = SanksiDisiplin::create([
                'karyawan_id' => $this->karyawanId,
                'pengusul_id' => $pengusul->id,
                'tingkat' => $tingkat,
                'uraian' => $this->uraian,
                'tanggal_kejadian' => $this->tanggalKejadian,
                'status' => StatusSanksi::Diajukan,
            ]);

            RantaiSanksi::bangunUntuk($sanksi);
            $sanksi->tahapAktif()?->approver->user?->notify(new SanksiPerluPersetujuan($sanksi));
        });

        session()->flash('disiplin_ok', 'Usulan sanksi terkirim.');

        return $this->redirectRoute('disiplin');
    }

    public function render()
    {
        $pengusul = $this->pengusul();
        $cari = trim($this->cari);

        return view('livewire.disiplin.usul-disiplin', [
            'pengusul' => $pengusul,
            'usulan' => $pengusul->usulanSanksi()->with(['karyawan', 'approval'])->get(),
            'hasilCari' => $cari !== '' && ! $this->karyawanId
                ? $this->bawahanQuery()
                    ->where(fn ($q) => $q->where('nama_lengkap', 'like', "%{$cari}%")->orWhere('nip', 'like', "%{$cari}%"))
                    ->limit(8)->get()
                : collect(),
            'karyawanTerpilih' => $this->karyawanId ? Karyawan::find($this->karyawanId) : null,
            'sanksiAktif' => $this->karyawanId ? EskalasiSanksi::sanksiAktif(Karyawan::find($this->karyawanId)) : collect(),
            'saran' => $this->karyawanId ? EskalasiSanksi::sarankan(Karyawan::find($this->karyawanId)) : null,
            'tingkatOpsi' => TingkatSanksi::cases(),
            'rantai' => RantaiSanksi::susun($pengusul, $this->karyawanId ? Karyawan::find($this->karyawanId) : null),
        ]);
    }
}
