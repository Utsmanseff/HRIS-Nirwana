<?php

// database/factories/KaryawanFactory.php

namespace Database\Factories;

use App\Enums\KodeJenisIzin;
use App\Models\IzinKaryawan;
use App\Models\Jabatan;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\OrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class KaryawanFactory extends Factory
{
    protected $model = Karyawan::class;

    public function definition(): array
    {
        return [
            'nip' => $this->faker->unique()->numerify('19##.##.##.###'),
            'nama_lengkap' => $this->faker->name(),
            'nik' => $this->faker->numerify('################'),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'no_hp' => $this->faker->numerify('08##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'org_unit_id' => null,
            'jabatan_id' => Jabatan::factory(),
            'tanggal_masuk' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'status' => 'aktif',
        ];
    }

    /** Pimpinan sebuah unit (level 2 koordinator / 3 kabid / 4 direktur). */
    public function pimpinanUnit(OrgUnit $unit, int $level = 2): static
    {
        return $this->state(fn () => [
            'jabatan_id' => Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => $level])->id,
            'org_unit_id' => $unit->id,
        ]);
    }

    /** Staff (level 1) di sebuah unit. */
    public function staffUnit(OrgUnit $unit): static
    {
        return $this->state(fn () => [
            'jabatan_id' => Jabatan::factory()->create(['org_unit_id' => $unit->id, 'level' => 1])->id,
            'org_unit_id' => $unit->id,
        ]);
    }

    /** Karyawan nakes dengan satu SIP berlaku (baris di izin_karyawan). */
    public function withIzinSip(): static
    {
        return $this->afterCreating(function (Karyawan $kar) {
            $sip = JenisIzin::firstOrCreate(
                ['kode' => KodeJenisIzin::Sip->value],
                ['nama' => 'SIP', 'ambang_hari' => 30, 'aktif' => true],
            );

            IzinKaryawan::factory()->create([
                'karyawan_id' => $kar->id,
                'jenis_izin_id' => $sip->id,
                'nomor' => 'SIP/'.$this->faker->numberBetween(100, 999).'/'.now()->year,
                'berlaku_mulai' => now()->subYears(2)->toDateString(),
                'berlaku_akhir' => now()->addYears(3)->toDateString(),
            ]);
        });
    }
}
