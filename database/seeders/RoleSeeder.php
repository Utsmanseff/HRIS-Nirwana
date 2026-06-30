<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (Permission::cases() as $p) {
            SpatiePermission::findOrCreate($p->value, 'web');
        }
        $map = [
            Role::Karyawan->value => [Permission::LihatDataSendiri, Permission::AjukanCutiAbsen],
            Role::StaffHr->value => [Permission::LihatDataSendiri, Permission::AjukanCutiAbsen, Permission::KelolaSdm, Permission::LihatLaporan],
            Role::Hrd->value => [Permission::LihatDataSendiri, Permission::AjukanCutiAbsen, Permission::KelolaSdm, Permission::AccCutiFinal, Permission::LihatLaporan],
            Role::It->value => [Permission::LihatDataSendiri, Permission::AjukanCutiAbsen, Permission::KerjakanTiketIt],
            Role::Teknisi->value => [Permission::LihatDataSendiri, Permission::AjukanCutiAbsen, Permission::KerjakanTiketSarana],
            Role::Atem->value => [Permission::LihatDataSendiri, Permission::AjukanCutiAbsen, Permission::KerjakanTiketAlkes],
            Role::Direktur->value => [Permission::LihatDataSendiri, Permission::LihatLaporan],
            Role::AdminSistem->value => [], // bypass via Gate::before
        ];
        foreach ($map as $roleName => $perms) {
            SpatieRole::findOrCreate($roleName, 'web')
                ->syncPermissions(array_map(fn (Permission $p) => $p->value, $perms));
        }
    }
}
