<?php

namespace Database\Seeders;

use App\Enums\JabatanLevel;
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
        $bidang = OrgUnit::create(['nama' => 'Penunjang Medik', 'tipe' => OrgUnitTipe::Bidang->value]);
        $it = OrgUnit::create(['nama' => 'IT', 'tipe' => OrgUnitTipe::Divisi->value, 'parent_id' => $bidang->id]);
        $jab = Jabatan::create(['nama' => 'Admin Sistem', 'level' => JabatanLevel::Koordinator->value]);

        $adminKar = Karyawan::create([
            'nip' => 'ADMIN-0001', 'nama_lengkap' => 'Administrator Sistem',
            'email' => 'admin@rsunirwana.test', 'org_unit_id' => $it->id, 'jabatan_id' => $jab->id,
            'tanggal_masuk' => now()->subYears(3), 'status' => 'aktif',
        ]);
        Kontrak::create(['karyawan_id' => $adminKar->id, 'jenis' => JenisKontrak::Tetap->value, 'tanggal_mulai' => now()->subYears(3)]);
        $admin = User::create([
            'karyawan_id' => $adminKar->id, 'name' => $adminKar->nama_lengkap,
            'email' => $adminKar->email, 'password' => Hash::make('password'),
        ]);
        $admin->assignRole(Role::AdminSistem->value);

        // 8 karyawan demo (untuk list/dashboard) — sebagian PKWT mendekati/melewati akhir.
        Karyawan::factory()->count(8)->create(['org_unit_id' => $it->id, 'jabatan_id' => $jab->id])
            ->each(fn ($k) => Kontrak::factory()->for($k)->create([
                'jenis' => JenisKontrak::Pkwt->value,
                'tanggal_mulai' => now()->subMonths(10),
                'tanggal_akhir' => now()->addDays(rand(-10, 60)),
            ]));
    }
}
