<?php

namespace App\Livewire\Absensi;

use App\Enums\ModeTemplate;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
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

    public function mount(): void
    {
        $unit = $this->unitDipimpin()->first();
        $this->unitId ??= $unit?->id;
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
        $maks = 1;
        foreach ($tpl?->baris ?? [] as $b) {
            $grid[$b->karyawan_id][$b->posisi] = $b->shift_id ? ($kodeById[$b->shift_id] ?? '') : 'L';
            $maks = max($maks, $b->posisi + 1);
        }
        $this->polaGrid = $grid;
        $this->tplPanjang = $this->tplMode === 'mingguan' ? 7 : ($tpl ? $maks : 7);
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

    public function simpanTemplate(): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);
        $this->validate(['tplJangkar' => ['required', 'date'], 'tplPanjang' => ['required', 'integer', 'min:1', 'max:60']]);

        $panjang = $this->tplMode === 'mingguan' ? 7 : $this->tplPanjang;
        $peta = $this->shiftIdByKode();

        // Validasi kode dikenal (selain libur).
        foreach ($this->polaGrid as $baris) {
            foreach ($baris as $kode) {
                if ($this->kodeLibur((string) $kode)) {
                    continue;
                }
                if (! isset($peta[strtoupper(trim((string) $kode))])) {
                    $this->addError('polaGrid', "Kode \"{$kode}\" tidak dikenal di unit ini.");

                    return;
                }
            }
        }

        DB::transaction(function () use ($peta, $panjang) {
            $tpl = TemplateJadwal::updateOrCreate(
                ['org_unit_id' => $this->unitId],
                ['tanggal_jangkar' => $this->tplJangkar, 'mode' => $this->tplMode],
            );
            PolaJadwal::where('template_id', $tpl->id)->delete();

            foreach ($this->polaGrid as $karyawanId => $baris) {
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
        ]);
    }
}
