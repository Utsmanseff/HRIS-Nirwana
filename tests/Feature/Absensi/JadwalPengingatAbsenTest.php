<?php

namespace Tests\Feature\Absensi;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class JadwalPengingatAbsenTest extends TestCase
{
    public function test_command_terjadwal_tiap_sepuluh_menit(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'absensi:kirim-pengingat'));

        $this->assertCount(1, $events);
        $this->assertSame('*/10 * * * *', $events->first()->expression);
    }
}
