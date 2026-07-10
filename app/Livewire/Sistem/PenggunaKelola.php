<?php

namespace App\Livewire\Sistem;

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\StatusKaryawan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role as SpatieRole;

#[Layout('components.layouts.app')]
class PenggunaKelola extends Component
{
    use WithPagination;

    /** Deskripsi singkat per role untuk kartu di tab Role. */
    private const DESKRIPSI_ROLE = [
        'Karyawan' => 'Role dasar semua orang — lihat data sendiri, ajukan cuti/absen. Diberikan otomatis saat klaim.',
        'Staff HR' => 'Kelola karyawan, kontrak, org, jabatan. Tidak bisa acc cuti.',
        'HRD' => 'Hak Staff HR + acc cuti final. Kepala SDM, satu orang.',
        'IT' => 'Kerjakan tiket & aset tim IT.',
        'Teknisi' => 'Kerjakan tiket & aset tim Sarana.',
        'ATEM' => 'Kerjakan tiket & aset alat medis.',
        'Direktur' => 'Approve level atas (Kabid & HRD) + lihat semua laporan.',
        'Admin Sistem' => 'Akses penuh seluruh aplikasi (bypass RBAC).',
    ];

    /** Label Indonesia per permission untuk baris matriks. */
    private const LABEL_PERMISSION = [
        'lihat-data-sendiri' => 'Lihat data sendiri',
        'ajukan-cuti-absen' => 'Ajukan cuti / absen',
        'kelola-sdm' => 'Kelola data SDM',
        'acc-cuti-final' => 'Acc cuti final',
        'kerjakan-tiket-it' => 'Kerjakan tiket IT',
        'kerjakan-tiket-sarana' => 'Kerjakan tiket Sarana',
        'kerjakan-tiket-alkes' => 'Kerjakan tiket Alkes',
        'lihat-laporan' => 'Lihat laporan',
        'kelola-rbac' => 'Kelola pengguna & role',
        'pengaturan-sistem' => 'Pengaturan sistem',
    ];

    #[Url]
    public string $tab = 'pengguna';

    #[Url]
    public string $q = '';

    #[Url]
    public string $filterRole = '';

    #[Url]
    public string $filterStatus = '';

    public ?int $kelolaId = null;

    /** @var list<string> */
    public array $rolePilihan = [];

    public ?string $sandiSementara = null;

    public bool $modeBuat = false;

    public string $cariBaru = '';

    public ?string $sandiBaru = null;

    public function bukaKelola(int $id): void
    {
        $user = User::findOrFail($id);
        $this->kelolaId = $user->id;
        $this->rolePilihan = $user->roles->pluck('name')->all();
        $this->sandiSementara = null;
        $this->resetErrorBag();
    }

    public function tutupKelola(): void
    {
        $this->reset(['kelolaId', 'rolePilihan', 'sandiSementara']);
        $this->resetErrorBag();
    }

    public function bukaBuat(): void
    {
        $this->modeBuat = true;
        $this->reset(['cariBaru', 'sandiBaru']);
        $this->resetErrorBag();
    }

    public function tutupBuat(): void
    {
        $this->reset(['modeBuat', 'cariBaru', 'sandiBaru']);
        $this->resetErrorBag();
    }

    /** Karyawan aktif yang belum punya akun (kandidat dibuatkan akun). */
    private function kandidatKaryawan()
    {
        if (mb_strlen(trim($this->cariBaru)) < 2) {
            return collect();
        }

        $term = '%'.trim($this->cariBaru).'%';

        return Karyawan::query()
            ->where('status', StatusKaryawan::Aktif->value)
            ->whereDoesntHave('user')
            ->where(fn ($w) => $w->where('nama_lengkap', 'like', $term)->orWhere('nip', 'like', $term))
            ->orderBy('nama_lengkap')
            ->limit(10)
            ->get();
    }

    public function buatAkun(int $karyawanId): void
    {
        $kar = Karyawan::query()
            ->where('id', $karyawanId)
            ->where('status', StatusKaryawan::Aktif->value)
            ->whereDoesntHave('user')
            ->first();

        if (! $kar) {
            $this->addError('buat', 'Karyawan tidak valid atau sudah punya akun.');

            return;
        }

        $email = $kar->email;
        if (! $email || User::where('email', $email)->exists()) {
            $email = Str::of($kar->nip)->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-').'@nirwana.local';
        }

        $sandi = Str::password(12, symbols: false);

        $user = User::create([
            'karyawan_id' => $kar->id,
            'name' => $kar->nama_lengkap,
            'email' => $email,
            'password' => $sandi, // cast 'hashed' meng-hash
        ]);
        $user->assignRole(Role::Karyawan->value);

        $this->sandiBaru = $sandi;
        $this->cariBaru = '';
    }

    public function hapus(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        $user->delete();
        $this->reset(['kelolaId', 'rolePilihan', 'sandiSementara']);
    }

    /** Ambil user target aksi; tolak bila akun sendiri (anti terkunci sendiri). */
    private function targetKelola(): ?User
    {
        $user = User::findOrFail($this->kelolaId);

        if ($user->id === auth()->id()) {
            $this->addError('kelola', 'Tidak bisa mengubah akun sendiri.');

            return null;
        }

        return $user;
    }

    public function simpanRole(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        $valid = array_values(array_intersect(
            $this->rolePilihan,
            array_column(Role::cases(), 'value'),
        ));

        $user->syncRoles($valid);
        $this->rolePilihan = $valid;
        session()->flash('pesan', 'Role tersimpan.');
    }

    public function resetSandi(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        $sandi = Str::password(12, symbols: false);
        $user->update(['password' => $sandi]); // cast 'hashed' otomatis meng-hash

        $this->sandiSementara = $sandi;
    }

    public function toggleAktif(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        $user->update(['nonaktif_pada' => $user->akunAktif() ? now() : null]);
    }

    public function unlink(): void
    {
        if (! $user = $this->targetKelola()) {
            return;
        }

        // Lepas tautan + cabut semua role: akun kembali netral, data karyawan
        // bisa diklaim ulang (kasus salah klaim / penyalahgunaan identitas).
        $user->syncRoles([]);
        $user->update(['karyawan_id' => null]);
        $this->rolePilihan = [];
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['q', 'filterRole', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $users = User::query()
            ->with(['roles', 'karyawan'])
            ->when($this->q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->q}%")
                ->orWhere('email', 'like', "%{$this->q}%")
                ->orWhereHas('karyawan', fn ($k) => $k
                    ->where('nip', 'like', "%{$this->q}%")
                    ->orWhere('nama_lengkap', 'like', "%{$this->q}%"))))
            ->when($this->filterRole !== '', fn ($query) => $query->role($this->filterRole))
            ->when($this->filterStatus === 'aktif', fn ($query) => $query->whereNull('nonaktif_pada'))
            ->when($this->filterStatus === 'nonaktif', fn ($query) => $query->whereNotNull('nonaktif_pada'))
            ->orderBy('name')
            ->paginate(15);

        // Tab Role: kartu + matriks dari database (urutan ikut enum). Query kecil (8×10).
        $spatieRoles = SpatieRole::with('permissions')->withCount('users')->get()->keyBy('name');
        $daftarRole = collect(Role::cases())
            ->map(fn (Role $r) => $spatieRoles->get($r->value))
            ->filter()
            ->values();

        return view('livewire.sistem.pengguna-kelola', [
            'users' => $users,
            'semuaRole' => Role::cases(),
            'daftarRole' => $daftarRole,
            'deskripsiRole' => self::DESKRIPSI_ROLE,
            'daftarPermission' => Permission::cases(),
            'labelPermission' => self::LABEL_PERMISSION,
            'kandidat' => $this->modeBuat ? $this->kandidatKaryawan() : collect(),
        ]);
    }
}
