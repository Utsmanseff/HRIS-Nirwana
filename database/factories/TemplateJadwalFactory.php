<?php

namespace Database\Factories;

use App\Models\OrgUnit;
use App\Models\TemplateJadwal;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateJadwalFactory extends Factory
{
    protected $model = TemplateJadwal::class;

    public function definition(): array
    {
        return [
            'org_unit_id' => OrgUnit::factory(),
            'tanggal_jangkar' => now()->startOfMonth()->toDateString(),
            'mode' => \App\Enums\ModeTemplate::Rotasi->value,
        ];
    }
}
