<?php

namespace App\Livewire\Disiplin;

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

    /** Query bawahan = aktif dalam turunan unit pengusul, kecuali diri. */
    protected function bawahanQuery()
    {
        $pengusul = $this->pengusul();
        if (! $pengusul->org_unit_id) {
            return Karyawan::query()->whereRaw('1 = 0');
        }

        return Karyawan::query()
            ->whereIn('org_unit_id', OrgUnit::denganTurunan($pengusul->org_unit_id))
            ->where('status', StatusKaryawan::Aktif->value)
            ->where('id', '!=', $pengusul->id);
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
            $this->addError('karyawanId', 'Karyawan bukan bawahan Anda.');

            return null;
        }

        $pengusul = $this->pengusul();
        $tingkat = TingkatSanksi::from((int) $this->tingkat);

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
        ]);
    }
}
