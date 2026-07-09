<?php

namespace App\Livewire\Absensi;

use App\Models\OrgUnit;
use App\Models\Shift;
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

    public function render()
    {
        return view('livewire.absensi.jadwal-kelola', [
            'unitList' => $this->unitDipimpin(),
            'unit' => $this->unitTerpilih(),
            'daftarShift' => $this->unitId
                ? Shift::where('org_unit_id', $this->unitId)->orderBy('jam_mulai')->get()
                : collect(),
        ]);
    }
}
