<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavMenu
{
    /**
     * Registry nav — source of truth. 'route' = nama route (null = placeholder '#').
     * NAMA route, bukan URL: hindari panggil route() saat class load (route 'beranda' baru ada Task 6).
     * 'can' = permission string wajib; null = selalu tampil.
     * 'group' = grup sidebar; item grup null muncul juga di bottom-nav/grid.
     *
     * @return list<array{id:string,label:string,icon:string,route:?string,can:?string,group:?string}>
     */
    public static function semua(): array
    {
        return [
            ['id' => 'beranda',  'label' => 'Beranda',        'icon' => 'home',     'route' => 'beranda',        'can' => null, 'group' => null],
            ['id' => 'cuti',     'label' => 'Cuti',           'icon' => 'calendar', 'route' => 'cuti',           'can' => Permission::AjukanCutiAbsen->value, 'group' => 'Operasional'],
            ['id' => 'persetujuan', 'label' => 'Persetujuan Cuti', 'icon' => 'check-circle', 'route' => 'cuti.persetujuan', 'can' => 'approve-cuti', 'group' => 'Operasional'],
            ['id' => 'kelola-cuti', 'label' => 'Kelola Cuti', 'icon' => 'sliders', 'route' => 'cuti.kelola', 'can' => 'kelola-cuti', 'group' => 'Operasional'],
            ['id' => 'laporan-cuti', 'label' => 'Laporan Cuti', 'icon' => 'chart', 'route' => 'cuti.laporan', 'can' => 'kelola-cuti', 'group' => 'Operasional'],
            ['id' => 'absensi',  'label' => 'Absensi',        'icon' => 'clock',    'route' => null,             'can' => Permission::AjukanCutiAbsen->value, 'group' => 'Operasional'],
            ['id' => 'tiket',    'label' => 'Tiket',          'icon' => 'ticket',   'route' => 'tiket',          'can' => null, 'group' => 'Operasional'],
            ['id' => 'inventaris', 'label' => 'Inventaris',   'icon' => 'box',      'route' => 'inventaris',     'can' => 'kelola-inventaris', 'group' => 'Operasional'],
            ['id' => 'disiplin', 'label' => 'Disiplin',       'icon' => 'gavel',    'route' => 'disiplin',       'can' => 'usul-disiplin', 'group' => 'Operasional'],
            ['id' => 'disiplin-persetujuan', 'label' => 'Persetujuan Sanksi', 'icon' => 'check-circle', 'route' => 'disiplin.persetujuan', 'can' => 'approve-disiplin', 'group' => 'Operasional'],
            ['id' => 'disiplin-kelola', 'label' => 'Kelola Sanksi', 'icon' => 'sliders', 'route' => 'disiplin.kelola', 'can' => 'buat-sanksi', 'group' => 'Operasional'],
            ['id' => 'laporan-disiplin', 'label' => 'Laporan Sanksi', 'icon' => 'chart', 'route' => 'disiplin.laporan', 'can' => 'kelola-disiplin', 'group' => 'Operasional'],
            ['id' => 'karyawan', 'label' => 'Karyawan',       'icon' => 'users',    'route' => 'sdm.karyawan',   'can' => Permission::KelolaSdm->value, 'group' => 'SDM'],
            ['id' => 'struktur', 'label' => 'Organisasi',     'icon' => 'tree',     'route' => 'sdm.struktur',   'can' => Permission::KelolaSdm->value, 'group' => 'SDM'],
            ['id' => 'pengguna', 'label' => 'Pengguna & Role','icon' => 'shield',   'route' => 'sistem.pengguna','can' => Permission::KelolaRbac->value, 'group' => 'Sistem'],
            ['id' => 'profil',   'label' => 'Profil',         'icon' => 'user',     'route' => 'profil',         'can' => null, 'group' => null],
        ];
    }

    /** Item yang lolos permission user. */
    public static function untuk(User $user): array
    {
        return array_values(array_filter(
            self::semua(),
            fn (array $it) => $it['can'] === null || $user->can($it['can']),
        ));
    }

    /** Resolve URL item: route bernama & terdaftar → URL, selain itu → '#' (placeholder). */
    public static function href(array $item): string
    {
        return $item['route'] && Route::has($item['route']) ? route($item['route']) : '#';
    }
}
