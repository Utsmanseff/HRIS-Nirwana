<?php

namespace Database\Seeders;

use App\Enums\JenisKontrak;
use App\Enums\OrgUnitTipe;
use App\Enums\Role;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Kontrak;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSdmSeeder extends Seeder
{
    public function run(): void
    {
        // ── Struktur: Direktorat > 2 Bidang > beberapa Unit ────────────────
        $direktorat = OrgUnit::create(['nama' => 'Direktorat', 'tipe' => OrgUnitTipe::Direktur->value]);
        $penunjang = OrgUnit::create(['nama' => 'Penunjang Medik', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $direktorat->id]);
        $keperawatan = OrgUnit::create(['nama' => 'Keperawatan', 'tipe' => OrgUnitTipe::Bidang->value, 'parent_id' => $direktorat->id]);

        $farmasi = OrgUnit::create(['nama' => 'Farmasi', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $penunjang->id]);
        $it = OrgUnit::create(['nama' => 'IT', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $penunjang->id]);
        $igd = OrgUnit::create(['nama' => 'IGD', 'tipe' => OrgUnitTipe::Unit->value, 'parent_id' => $keperawatan->id]);

        // ── Pimpinan tiap tingkat ──────────────────────────────────────────
        $this->pimpinan($direktorat, 'Direktur', 4, 'Dr. Direktur Utama', 'DIR-0001', tetap: true);
        $this->pimpinan($penunjang, 'Kepala Bidang Penunjang', 3, 'Kabid Penunjang', 'KBD-0001', tetap: true);
        $this->pimpinan($keperawatan, 'Kepala Bidang Keperawatan', 3, 'Kabid Keperawatan', 'KBD-0002', tetap: true);
        $this->pimpinan($farmasi, 'Koordinator Farmasi', 2, 'Koor Farmasi', 'KOR-0001', tetap: true);
        $this->pimpinan($igd, 'Koordinator IGD', 2, 'Koor IGD', 'KOR-0002', tetap: true);

        // ── Admin Sistem = Koordinator IT + akun bootstrap ─────────────────
        $adminKar = $this->pimpinan($it, 'Koordinator IT', 2, 'Administrator Sistem', 'ADMIN-0001', tetap: true, email: 'admin@rsunirwana.test');
        $admin = User::create([
            'karyawan_id' => $adminKar->id, 'name' => $adminKar->nama_lengkap,
            'email' => $adminKar->email, 'password' => Hash::make('password'),
        ]);
        $admin->assignRole(Role::AdminSistem->value);

        // ── Staff PKWT (sebagian mendekati/melewati akhir kontrak) ─────────
        $unitStaff = [$farmasi, $it, $igd];
        foreach ($unitStaff as $unit) {
            $jabStaff = Jabatan::create(['nama' => 'Staff '.$unit->nama, 'level' => 1, 'org_unit_id' => $unit->id]);
            Karyawan::factory()->count(3)->create(['jabatan_id' => $jabStaff->id, 'org_unit_id' => $unit->id])
                ->each(fn ($k) => Kontrak::factory()->for($k)->create([
                    'jenis' => JenisKontrak::Pkwt->value,
                    'tanggal_mulai' => now()->subMonths(10),
                    'tanggal_akhir' => now()->addDays(rand(-10, 60)),
                ]));
        }
    }

    /** Buat 1 pimpinan (jabatan level + karyawan + kontrak tetap/pkwt) untuk sebuah unit. */
    private function pimpinan(OrgUnit $unit, string $namaJabatan, int $level, string $nama, string $nip, bool $tetap = false, ?string $email = null): Karyawan
    {
        $jab = Jabatan::create(['nama' => $namaJabatan, 'level' => $level, 'org_unit_id' => $unit->id]);
        $kar = Karyawan::create([
            'nip' => $nip, 'nama_lengkap' => $nama, 'email' => $email ?? strtolower(str_replace(' ', '.', $nip)).'@rsunirwana.test',
            'org_unit_id' => $unit->id, 'jabatan_id' => $jab->id,
            'tanggal_masuk' => now()->subYears(3), 'status' => 'aktif',
        ]);
        Kontrak::create([
            'karyawan_id' => $kar->id,
            'jenis' => $tetap ? JenisKontrak::Tetap->value : JenisKontrak::Pkwt->value,
            'tanggal_mulai' => now()->subYears(3),
            'tanggal_akhir' => $tetap ? null : now()->addYear(),
        ]);

        return $kar;
    }
}
