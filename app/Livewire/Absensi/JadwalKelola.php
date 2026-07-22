<?php

namespace App\Livewire\Absensi;

use App\Enums\ModeTemplate;
use App\Models\Jadwal;
use App\Models\OrgUnit;
use App\Models\PolaJadwal;
use App\Models\Shift;
use App\Models\TemplateJadwal;
use App\Support\JadwalHarian;
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
    #[Url]
    public ?int $polaId = null;

    public string $pNama = '';          // nama pola (buat / ubah)
    public bool $formPola = false;      // form buat pola terbuka
    public string $modeFormPola = 'buat';   // buat | ubah
    public string $cariAnggota = '';

    /** karyawan_id baris yang sedang menunggu lawan tukar (null = tak ada). */
    public ?int $tukarDari = null;

    public string $tplMode = 'rotasi';   // rotasi | mingguan
    public string $tplJangkar = '';
    public int $tplPanjang = 7;
    /** polaGrid[karyawan_id][posisi] = kode (string, '' / 'L' = libur). */
    public array $polaGrid = [];

    /** panjangBaris[karyawan_id] = panjang siklus baris (mode rotasi). */
    public array $panjangBaris = [];

    /**
     * Urutan anggota pola (daftar karyawan_id, urut penambahan). Dipakai untuk
     * tampilan & urutan simpan. WAJIB list berindeks 0..n (bukan peta) supaya
     * kebal reorder-kunci-integer yang dilakukan JS pada objek polaGrid.
     */
    public array $urutanAnggota = [];

    public const PANJANG_DEFAULT = 7;

    // ── Tab Jadwal Bulanan ───────────────────────────────────
    public ?int $tahun = null;
    public ?int $bulan = null;

    public function mount(): void
    {
        $unit = $this->unitDipimpin()->first();
        $this->unitId ??= $unit?->id;
        $this->polaId ??= $this->daftarPola()->first()?->id;
        $this->tahun ??= now()->year;
        $this->bulan ??= now()->month;

        if ($this->tab === 'template' && $this->unitId) {
            $this->muatTemplate();   // muat saat load langsung / refresh di tab template
        }
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
            $this->polaId = $this->daftarPola()->first()?->id;
            $this->muatTemplate();
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
    /** Semua pola milik unit terpilih, urut nama. */
    public function daftarPola()
    {
        return $this->unitId ? TemplateJadwal::untukUnit($this->unitId)->get() : collect();
    }

    /** Pola aktif (yang gridnya sedang disunting), atau null. */
    public function polaAktif(): ?TemplateJadwal
    {
        return $this->polaId ? TemplateJadwal::where('org_unit_id', $this->unitId)->find($this->polaId) : null;
    }

    public function gantiPola(int $polaId): void
    {
        if (! TemplateJadwal::where('org_unit_id', $this->unitId)->whereKey($polaId)->exists()) {
            return;
        }
        $this->polaId = $polaId;
        $this->muatTemplate();
    }

    public function bukaFormPola(): void
    {
        $this->formPola = true;
        $this->modeFormPola = 'buat';
        $this->pNama = '';
        $this->resetErrorBag('pNama');
    }

    public function bukaFormUbahNama(): void
    {
        $pola = $this->polaAktif();
        if (! $pola) {
            return;
        }
        $this->formPola = true;
        $this->modeFormPola = 'ubah';
        $this->pNama = $pola->nama;
        $this->resetErrorBag('pNama');
    }

    public function batalFormPola(): void
    {
        $this->formPola = false;
        $this->modeFormPola = 'buat';
        $this->pNama = '';
        $this->resetErrorBag('pNama');
    }

    public function buatPola(): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);

        $this->validate([
            'pNama' => ['required', 'string', 'max:60', Rule::unique('template_jadwal', 'nama')->where('org_unit_id', $this->unitId)],
        ], attributes: ['pNama' => 'nama pola']);

        $pola = TemplateJadwal::create([
            'org_unit_id' => $this->unitId,
            'nama' => trim($this->pNama),
            'tanggal_jangkar' => now()->startOfMonth()->toDateString(),
            'mode' => 'rotasi',
        ]);

        $this->polaId = $pola->id;
        $this->batalFormPola();
        $this->muatTemplate();
    }

    public function ubahNamaPola(): void
    {
        $pola = $this->polaAktif();
        abort_unless($pola && $this->unitDipimpin()->contains('id', $this->unitId), 403);

        $this->validate([
            'pNama' => ['required', 'string', 'max:60', Rule::unique('template_jadwal', 'nama')
                ->where('org_unit_id', $this->unitId)->ignore($pola->id)],
        ], attributes: ['pNama' => 'nama pola']);

        $pola->update(['nama' => trim($this->pNama)]);
        $this->batalFormPola();
    }

    /** Hapus pola. Jadwal yang sudah terbentuk TIDAK ikut dihapus — itu data nyata. */
    public function hapusPola(): void
    {
        $pola = $this->polaAktif();
        abort_unless($pola && $this->unitDipimpin()->contains('id', $this->unitId), 403);

        $pola->delete();                       // pola_jadwal ikut lewat cascade
        $this->polaId = $this->daftarPola()->first()?->id;
        $this->muatTemplate();
    }

    public function muatTemplate(): void
    {
        $tpl = $this->polaAktif()?->load('baris');
        $this->tplMode = $tpl?->mode?->value ?? 'rotasi';
        $this->tplJangkar = $tpl?->tanggal_jangkar?->toDateString() ?? now()->startOfMonth()->toDateString();

        $kodeById = $this->kodeShiftById();
        $grid = [];
        $panjang = [];
        $urutan = [];
        // baris diurut id → urutan kemunculan pertama = urutan penambahan asli.
        foreach (($tpl?->baris ?? collect())->sortBy('id') as $b) {
            if (! isset($grid[$b->karyawan_id])) {
                $urutan[] = $b->karyawan_id;
            }
            $grid[$b->karyawan_id][$b->posisi] = $b->shift_id ? ($kodeById[$b->shift_id] ?? '') : 'L';
            $panjang[$b->karyawan_id] = max($panjang[$b->karyawan_id] ?? 1, $b->posisi + 1);
        }
        $this->polaGrid = $grid;
        $this->panjangBaris = $panjang;
        $this->urutanAnggota = $urutan;
        $this->tukarDari = null;
    }

    /** Daftar karyawan_id di grid, urut penambahan (fallback kunci grid). */
    protected function urutanGrid(): array
    {
        $urut = array_values(array_filter($this->urutanAnggota, fn ($id) => isset($this->polaGrid[$id])));
        foreach (array_keys($this->polaGrid) as $kid) {
            if (! in_array($kid, $urut, true)) {
                $urut[] = $kid;
            }
        }

        return $urut;
    }

    public function tambahKaryawan(int $karyawanId): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);

        $boleh = auth()->user()->karyawan->karyawanKelolaan()->whereKey($karyawanId)->exists();
        if (! $boleh || isset($this->polaGrid[$karyawanId])) {
            return;
        }

        $panjang = $this->tplMode === 'mingguan' ? 7 : self::PANJANG_DEFAULT;
        $this->polaGrid[$karyawanId] = array_fill(0, $panjang, '');
        $this->panjangBaris[$karyawanId] = $panjang;
        if (! in_array($karyawanId, $this->urutanAnggota, true)) {
            $this->urutanAnggota[] = $karyawanId;
        }
        $this->cariAnggota = '';
    }

    /** Hasil pencarian anggota: kelolaan yang belum ada di grid, disaring nama/NIP. */
    public function hasilCariAnggota()
    {
        $kunci = trim($this->cariAnggota);
        if ($kunci === '' || ! $this->unitId) {
            return collect();
        }

        return auth()->user()->karyawan->karyawanKelolaan()
            ->where(fn ($q) => $q->where('nama_lengkap', 'like', '%'.$kunci.'%')
                ->orWhere('nip', 'like', '%'.$kunci.'%'))
            ->whereNotIn('id', array_keys($this->polaGrid) ?: [-1])
            ->orderBy('nama_lengkap')
            ->limit(8)
            ->get();
    }

    /** peta karyawan_id => nama pola lain yang memuatnya (untuk penanda "sudah di Pola X"). */
    public function polaLainPeta(): array
    {
        if (! $this->unitId) {
            return [];
        }

        return PolaJadwal::query()
            ->join('template_jadwal', 'template_jadwal.id', '=', 'pola_jadwal.template_id')
            ->where('template_jadwal.org_unit_id', $this->unitId)
            ->when($this->polaId, fn ($q) => $q->where('template_jadwal.id', '!=', $this->polaId))
            ->pluck('template_jadwal.nama', 'pola_jadwal.karyawan_id')
            ->all();
    }

    public function hapusBaris(int $karyawanId): void
    {
        unset($this->polaGrid[$karyawanId], $this->panjangBaris[$karyawanId]);
        $this->urutanAnggota = array_values(array_filter($this->urutanAnggota, fn ($id) => $id !== $karyawanId));
        if ($this->tukarDari === $karyawanId) {
            $this->tukarDari = null;
        }
    }

    /** Tandai baris sebagai asal tukar; klik lagi pada baris yang sama membatalkan. */
    public function pilihTukar(int $karyawanId): void
    {
        $this->tukarDari = ($this->tukarDari === $karyawanId) ? null : $karyawanId;
    }

    /** Tukar isi siklus + panjang siklus antara baris asal dan baris tujuan. */
    public function tukarBaris(int $karyawanId): void
    {
        $dari = $this->tukarDari;
        $this->tukarDari = null;

        if ($dari === null || $dari === $karyawanId) {
            return;
        }
        if (! isset($this->polaGrid[$dari], $this->polaGrid[$karyawanId])) {
            return;
        }

        [$this->polaGrid[$dari], $this->polaGrid[$karyawanId]] =
            [$this->polaGrid[$karyawanId], $this->polaGrid[$dari]];

        $panjangDari = $this->panjangBaris[$dari] ?? self::PANJANG_DEFAULT;
        $panjangTujuan = $this->panjangBaris[$karyawanId] ?? self::PANJANG_DEFAULT;
        $this->panjangBaris[$dari] = $panjangTujuan;
        $this->panjangBaris[$karyawanId] = $panjangDari;
    }

    public function tambahKolom(int $karyawanId): void
    {
        if (! isset($this->panjangBaris[$karyawanId])) {
            return;
        }
        $this->panjangBaris[$karyawanId] = min(60, (int) $this->panjangBaris[$karyawanId] + 1);
    }

    public function kurangKolom(int $karyawanId): void
    {
        if (! isset($this->panjangBaris[$karyawanId])) {
            return;
        }
        $baru = max(1, (int) $this->panjangBaris[$karyawanId] - 1);
        unset($this->polaGrid[$karyawanId][$baru]);   // buang sel terpangkas (posisi == panjang baru)
        $this->panjangBaris[$karyawanId] = $baru;
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

        $pola = $this->polaAktif();
        if (! $pola) {
            $this->addError('polaGrid', 'Pilih atau buat pola dulu.');

            return;
        }

        DB::transaction(function () use ($peta, $mingguan, $pola) {
            $pola->update(['tanggal_jangkar' => $this->tplJangkar, 'mode' => $this->tplMode]);
            PolaJadwal::where('template_id', $pola->id)->delete();

            // Satu karyawan maksimal satu pola per unit: buang keanggotaannya di pola lain.
            $polaLain = TemplateJadwal::where('org_unit_id', $this->unitId)
                ->whereKeyNot($pola->id)->pluck('id');
            PolaJadwal::whereIn('template_id', $polaLain)
                ->whereIn('karyawan_id', array_keys($this->polaGrid))
                ->delete();

            // Urut penambahan (bukan urutan kunci grid yang bisa di-reorder JS).
            foreach ($this->urutanGrid() as $karyawanId) {
                $baris = (array) $this->polaGrid[$karyawanId];
                $panjang = $mingguan ? 7 : $this->panjangSiklus((int) $karyawanId, $baris);
                for ($posisi = 0; $posisi < $panjang; $posisi++) {
                    $kode = (string) ($baris[$posisi] ?? '');
                    $shiftId = $this->kodeLibur($kode) ? null : ($peta[strtoupper(trim($kode))] ?? null);
                    PolaJadwal::create([
                        'template_id' => $pola->id,
                        'karyawan_id' => $karyawanId,
                        'posisi' => $posisi,
                        'shift_id' => $shiftId,
                    ]);
                }
            }
        });

        session()->flash('sukses', 'Pola disimpan.');
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

    /** Urai isi sel "P,M" / "p+m" → ['P','M'] unik. Kosong / L / LIBUR diabaikan. */
    protected function uraiKode(string $teks): array
    {
        $hasil = [];
        foreach (preg_split('/[,+]/', $teks) ?: [] as $bagian) {
            $kode = strtoupper(trim((string) $bagian));
            if ($this->kodeLibur($kode) || in_array($kode, $hasil, true)) {
                continue;
            }
            $hasil[] = $kode;
        }

        return $hasil;
    }

    /** Isi satu sel grid. Dinas ganda: beberapa kode dipisah koma, mis. "M,S". */
    public function setSel(int $karyawanId, int $hari, string $teks): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);
        abort_unless(auth()->user()->karyawan->karyawanKelolaan()->whereKey($karyawanId)->exists(), 403);

        $this->resetErrorBag('jadwal');
        $tanggal = Carbon::create($this->tahun, $this->bulan, $hari)->toDateString();
        $kode = $this->uraiKode($teks);

        // whereDate agar cocok kolom date 'Y-m-d 00:00:00'.
        if ($kode === []) {
            Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tanggal)->delete();

            return;
        }

        $peta = $this->shiftIdByKode();
        $shiftIds = [];
        foreach ($kode as $k) {
            if (! isset($peta[$k])) {
                $this->addError('jadwal', "Kode \"{$k}\" tidak dikenal.");

                return;
            }
            $shiftIds[] = $peta[$k];
        }

        // Baris yang bertahan selalu himpunan bagian dari yang diminta (sisanya dihapus),
        // jadi cukup periksa bentrok antar shift yang diminta.
        $daftarShift = Shift::whereIn('id', $shiftIds)->get()->values();
        foreach ($daftarShift as $i => $a) {
            foreach ($daftarShift as $j => $b) {
                if ($j <= $i) {
                    continue;
                }
                [$aMulai, $aSelesai] = JadwalHarian::rentang($a);
                [$bMulai, $bSelesai] = JadwalHarian::rentang($b);
                if ($aMulai < $bSelesai && $bMulai < $aSelesai) {
                    $this->addError('jadwal', "Shift {$a->kode} dan {$b->kode} bentrok jamnya.");

                    return;
                }
            }
        }

        DB::transaction(function () use ($karyawanId, $tanggal, $shiftIds) {
            Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tanggal)
                ->whereNotIn('shift_id', $shiftIds)->delete();

            $ada = Jadwal::where('karyawan_id', $karyawanId)->whereDate('tanggal', $tanggal)
                ->pluck('shift_id')->all();

            foreach (array_diff($shiftIds, $ada) as $shiftId) {
                Jadwal::create([
                    'karyawan_id' => $karyawanId,
                    'tanggal' => $tanggal,
                    'shift_id' => $shiftId,
                    'dibuat_oleh' => auth()->id(),
                ]);
            }
        });
    }

    public function terapkanPola(int $polaId): void
    {
        abort_unless($this->unitDipimpin()->contains('id', $this->unitId), 403);

        $pola = TemplateJadwal::where('org_unit_id', $this->unitId)->findOrFail($polaId);
        $jumlah = TerapkanPola::untukPola($pola, $this->tahun, $this->bulan, auth()->id(), timpa: true);

        session()->flash('sukses', "Pola {$pola->nama} diterapkan: {$jumlah} jadwal.");
    }

    /**
     * Baris grid bulanan dikelompokkan: satu blok per pola (urut anggota sesuai pola),
     * lalu blok "Tanpa Pola" berisi sisa kelolaan.
     *
     * @return array<int, array{pola: ?\App\Models\TemplateJadwal, nama: string, karyawan: \Illuminate\Support\Collection}>
     */
    public function blokJadwal(): array
    {
        if (! $this->unitId) {
            return [];
        }

        $kelolaan = auth()->user()->karyawan->karyawanKelolaan()
            ->with('jabatan')->orderBy('nama_lengkap')->get()->keyBy('id');

        $blok = [];
        $terpakai = [];

        foreach ($this->daftarPola() as $pola) {
            // Urutan anggota = urutan penambahan (baris pola paling awal).
            $anggotaIds = PolaJadwal::where('template_id', $pola->id)
                ->selectRaw('karyawan_id, MIN(id) as urut')
                ->groupBy('karyawan_id')->orderBy('urut')->pluck('karyawan_id');

            $anggota = $anggotaIds->map(fn ($id) => $kelolaan[$id] ?? null)->filter()->values();
            $terpakai = array_merge($terpakai, $anggota->pluck('id')->all());

            $blok[] = ['pola' => $pola, 'nama' => $pola->nama, 'karyawan' => $anggota];
        }

        $sisa = $kelolaan->reject(fn ($k) => in_array($k->id, $terpakai, true))->values();
        if ($sisa->isNotEmpty()) {
            $blok[] = ['pola' => null, 'nama' => 'Tanpa Pola', 'karyawan' => $sisa];
        }

        return $blok;
    }

    /** petaJadwal[karyawan_id][hari] = daftar kode shift (urut jam mulai). */
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
            ->with('shift:id,kode,jam_mulai')
            ->get()
            ->sortBy(fn (Jadwal $j) => $j->shift?->jam_mulai ?? '99:99:99')
            ->each(function (Jadwal $j) use (&$peta) {
                if ($j->shift?->kode) {
                    $peta[$j->karyawan_id][(int) $j->tanggal->format('j')][] = $j->shift->kode;
                }
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
            'daftarPola' => $this->daftarPola(),
            'polaAktif' => $this->polaAktif(),
            'urutanGrid' => $this->urutanGrid(),
            'hasilCariAnggota' => $this->hasilCariAnggota(),
            'polaLainPeta' => $this->polaLainPeta(),
            'blokJadwal' => $this->blokJadwal(),
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
