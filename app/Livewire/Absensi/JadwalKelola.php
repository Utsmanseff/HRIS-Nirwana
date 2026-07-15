<?php

namespace App\Livewire\Absensi;

use App\Enums\ModeTemplate;
use App\Models\Jadwal;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use App\Support\TerapkanPola;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JadwalKelola extends Component
{
    #[Url]
    public string $tab = 'shift';   // shift | template | jadwal

    #[Url]
    public ?int $unitId = null;

    // ── Tab Shift ────────────────────────────────────────────
    public ?int $editShiftId = null;

    public string $sNama = '';
    public string $sKode = '';
    public string $sWarna = '#16A34A';
    public string $sMulai = '07:00';
    public string $sSelesai = '14:00';
    public int $sToleransi = 10;

    // ── Tab Template ─────────────────────────────────────────
    public string $tplMode = 'rotasi';   // rotasi | mingguan
    public string $tplJangkar = '';
    public int $tplPanjang = 7;
    /** polaGrid[karyawan_id][posisi] = kode (string, '' / 'L' = libur). */
    public array $polaGrid = [];

    /** panjangBaris[karyawan_id] = panjang siklus baris (mode rotasi). */
    public array $panjangBaris = [];

    public const PANJANG_DEFAULT = 7;

    // ── Tab Jadwal Bulanan ───────────────────────────────────
    public ?int $tahun = null;
    public ?int $bulan = null;

    public function mount(): void
    {
        $unit = $this->unitDipimpin()->first();
        $this->unitId ??= $unit?->id;
        $this->tahun ??= now()->year;
        $this->bulan ??= now()->month;
    }

    /** Unit-unit yang dipimpin user (untuk selektor). */
    public function unitDipimpin()
    {
        return auth()->user()->karyawan?->unitDipimpin() ?? collect();
    }

    public function gantiTab(string $tab): void
    {
        $this->tab = in_array($tab, ['shift', 'template', 'jadwal'], true) ? $tab : 'shift';
        if ($this->tab === 'template') {
            $this->muatTemplate();
        }
    }

    public function updatedTplMode(string $value): void
    {
        if ($value === 'mingguan') {
            $this->tplPanjang = 7;
        }
    }

    public function gantiUnit(int $unitId): void
    {
        if ($this->unitDipimpin()->contains('id', $unitId)) {
            $this->unitId = $unitId;
        }
    }

    protected function unitTerpilih(): ?OrgUnit
    {
        return $this->unitId ? OrgUnit::find($this->unitId) : null;
    }

    public function editShift(int $id): void
    {
        $shift = Shift::where('org_unit_id', $this->unitId)->findOrFail($id);
        $this->editShiftId = $shift->id;
        $this->sNama = $shift->nama;
        $this->sKode = $shift->kode;
        $this->sWarna = $shift->warna;
        $this->sMulai = substr($shift->jam_mulai, 0, 5);
        $this->sSelesai = substr($shift->jam_selesai, 0, 5);
        $this->sToleransi = $shift->toleransi_telat;
    }

    public function batalShift(): void
    {
        $this->reset(['editShiftId', 'sNama', 'sKode', 'sWarna', 'sMulai', 'sSelesai', 'sToleransi']);
    }

    public function simpanShift(): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);

        $data = $this->validate([
            'sNama' => ['required', 'string', 'max:60'],
            'sKode' => ['required', 'string', 'max:4', Rule::unique('shift', 'kode')
                ->where('org_unit_id', $this->unitId)->ignore($this->editShiftId)],
            'sWarna' => ['required', 'string', 'max:9'],
            'sMulai' => ['required', 'date_format:H:i'],
            'sSelesai' => ['required', 'date_format:H:i'],
            'sToleransi' => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        Shift::updateOrCreate(['id' => $this->editShiftId], [
            'org_unit_id' => $this->unitId,
            'nama' => $data['sNama'],
            'kode' => strtoupper($data['sKode']),
            'warna' => $data['sWarna'],
            'jam_mulai' => $data['sMulai'].':00',
            'jam_selesai' => $data['sSelesai'].':00',
            'toleransi_telat' => $data['sToleransi'],
            'aktif' => true,
        ]);

        $this->batalShift();
    }

    public function toggleShiftAktif(int $id): void
    {
        $shift = Shift::where('org_unit_id', $this->unitId)->findOrFail($id);
        $shift->update(['aktif' => ! $shift->aktif]);
    }

    // ── Tab Template ─────────────────────────────────────────
    public function muatTemplate(): void
    {
        $tpl = TemplateJadwal::where('org_unit_id', $this->unitId)->with('baris')->first();
        $this->tplMode = $tpl?->mode?->value ?? 'rotasi';
        $this->tplJangkar = $tpl?->tanggal_jangkar?->toDateString() ?? now()->startOfMonth()->toDateString();

        $kodeById = $this->kodeShiftById();
        $grid = [];
        $panjang = [];
        foreach ($tpl?->baris ?? [] as $b) {
            $grid[$b->karyawan_id][$b->posisi] = $b->shift_id ? ($kodeById[$b->shift_id] ?? '') : 'L';
            $panjang[$b->karyawan_id] = max($panjang[$b->karyawan_id] ?? 1, $b->posisi + 1);
        }
        $this->polaGrid = $grid;
        $this->panjangBaris = $panjang;
    }

    /** Peta kode(uppercase) → shift_id untuk unit terpilih. */
    protected function shiftIdByKode(): array
    {
        return Shift::where('org_unit_id', $this->unitId)->pluck('id', 'kode')
            ->mapWithKeys(fn ($id, $kode) => [strtoupper($kode) => $id])->all();
    }

    protected function kodeShiftById(): array
    {
        return Shift::where('org_unit_id', $this->unitId)->pluck('kode', 'id')->all();
    }

    protected function kodeLibur(string $kode): bool
    {
        $k = strtoupper(trim($kode));

        return $k === '' || $k === 'L' || $k === 'LIBUR';
    }

    /** Panjang siklus baris (rotasi): dari panjangBaris bila ada, else turunkan dari posisi tertinggi grid. */
    protected function panjangSiklus(int $karyawanId, array $baris): int
    {
        if (isset($this->panjangBaris[$karyawanId])) {
            return max(1, min(60, (int) $this->panjangBaris[$karyawanId]));
        }

        return empty($baris) ? 1 : ((int) max(array_keys($baris)) + 1);
    }

    public function simpanTemplate(): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);
        $this->validate(['tplJangkar' => ['required', 'date']]);

        $peta = $this->shiftIdByKode();

        // Validasi kode dikenal (selain libur).
        foreach ($this->polaGrid as $baris) {
            foreach ((array) $baris as $kode) {
                if ($this->kodeLibur((string) $kode)) {
                    continue;
                }
                if (! isset($peta[strtoupper(trim((string) $kode))])) {
                    $this->addError('polaGrid', "Kode \"{$kode}\" tidak dikenal di unit ini.");

                    return;
                }
            }
        }

        $mingguan = $this->tplMode === 'mingguan';

        DB::transaction(function () use ($peta, $mingguan) {
            $tpl = TemplateJadwal::updateOrCreate(
                ['org_unit_id' => $this->unitId],
                ['tanggal_jangkar' => $this->tplJangkar, 'mode' => $this->tplMode],
            );
            PolaJadwal::where('template_id', $tpl->id)->delete();

            foreach ($this->polaGrid as $karyawanId => $baris) {
                $panjang = $mingguan ? 7 : $this->panjangSiklus((int) $karyawanId, (array) $baris);
                for ($posisi = 0; $posisi < $panjang; $posisi++) {
                    $kode = (string) ($baris[$posisi] ?? '');
                    $shiftId = $this->kodeLibur($kode) ? null : ($peta[strtoupper(trim($kode))] ?? null);
                    PolaJadwal::create([
                        'template_id' => $tpl->id,
                        'karyawan_id' => $karyawanId,
                        'posisi' => $posisi,
                        'shift_id' => $shiftId,
                    ]);
                }
            }
        });

        session()->flash('sukses', 'Template pola disimpan.');
    }

    // ── Tab Jadwal Bulanan ───────────────────────────────────
    public function bulanSebelumnya(): void
    {
        $t = Carbon::create($this->tahun, $this->bulan, 1)->subMonth();
        $this->tahun = $t->year;
        $this->bulan = $t->month;
    }

    public function bulanBerikutnya(): void
    {
        $t = Carbon::create($this->tahun, $this->bulan, 1)->addMonth();
        $this->tahun = $t->year;
        $this->bulan = $t->month;
    }

    public function setSel(int $karyawanId, int $hari, string $kode): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);
        abort_unless(auth()->user()->karyawan->karyawanKelolaan()->whereKey($karyawanId)->exists(), 403);

        $tanggal = Carbon::create($this->tahun, $this->bulan, $hari)->toDateString();

        if ($this->kodeLibur($kode)) {
            Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tanggal)->delete();

            return;
        }

        $shiftId = $this->shiftIdByKode()[strtoupper(trim($kode))] ?? null;
        if (! $shiftId) {
            $this->addError('jadwal', "Kode \"{$kode}\" tidak dikenal.");

            return;
        }

        // whereDate agar cocok kolom date 'Y-m-d 00:00:00'.
        $row = Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tanggal)->first();
        if ($row) {
            $row->update(['shift_id' => $shiftId, 'dibuat_oleh' => auth()->id()]);
        } else {
            Jadwal::create(['karyawan_id' => $karyawanId, 'tanggal' => $tanggal, 'shift_id' => $shiftId, 'dibuat_oleh' => auth()->id()]);
        }
    }

    public function terapkanPola(): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);
        $jumlah = TerapkanPola::generate($this->unitTerpilih(), $this->tahun, $this->bulan, auth()->id(), timpa: true);
        session()->flash('sukses', "Pola diterapkan: {$jumlah} jadwal.");
    }

    /** petaJadwal[karyawan_id][hari] = kode shift (untuk isi grid). */
    protected function petaJadwalBulan(): array
    {
        if (! $this->unitId) {
            return [];
        }
        $kelolaanIds = auth()->user()->karyawan->karyawanKelolaan()->pluck('id')->all();
        $awal = Carbon::create($this->tahun, $this->bulan, 1)->toDateString();
        $akhir = Carbon::create($this->tahun, $this->bulan, 1)->endOfMonth()->toDateString();

        $peta = [];
        Jadwal::whereIn('karyawan_id', $kelolaanIds)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->with('shift:id,kode')
            ->get()
            ->each(function (Jadwal $j) use (&$peta) {
                $peta[$j->karyawan_id][(int) $j->tanggal->format('j')] = $j->shift?->kode;
            });

        return $peta;
    }

    public function render()
    {
        return view('livewire.absensi.jadwal-kelola', [
            'unitList' => $this->unitDipimpin(),
            'unit' => $this->unitTerpilih(),
            'daftarShift' => $this->unitId
                ? Shift::where('org_unit_id', $this->unitId)->orderBy('jam_mulai')->get()
                : collect(),
            'kelolaan' => $this->unitId
                ? auth()->user()->karyawan->karyawanKelolaan()->with('jabatan')->orderBy('nama_lengkap')->get()
                : collect(),
            'jumlahHari' => Carbon::create($this->tahun, $this->bulan, 1)->daysInMonth,
            'petaJadwal' => $this->petaJadwalBulan(),
            'namaBulan' => Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y'),
            'warnaKode' => $this->unitId
                ? Shift::where('org_unit_id', $this->unitId)->pluck('warna', 'kode')
                    ->mapWithKeys(fn ($w, $k) => [strtoupper($k) => $w])->all()
                : [],
        ]);
    }
}
