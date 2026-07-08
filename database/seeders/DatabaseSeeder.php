<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,   // harus duluan: DemoSdmSeeder pakai assignRole
            JenisCutiSeeder::class,
            DemoSdmSeeder::class,
            DemoInventarisSeeder::class, // setelah DemoSdmSeeder: butuh org_unit
            DemoTiketSeeder::class, // butuh aset (DemoInventaris) + user/karyawan
        ]);
    }
}
