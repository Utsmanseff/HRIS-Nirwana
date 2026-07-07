<?php

namespace Tests\Feature\Disiplin;

use App\Support\NavMenu;
use Tests\TestCase;

class NavMenuDisiplinTest extends TestCase
{
    public function test_item_disiplin_aktif_dengan_gate_usul(): void
    {
        $item = collect(NavMenu::semua())->firstWhere('id', 'disiplin');

        $this->assertNotNull($item);
        $this->assertSame('disiplin', $item['route']);
        $this->assertSame('usul-disiplin', $item['can']);
        $this->assertSame('gavel', $item['icon']);
    }

    public function test_href_placeholder_saat_route_belum_terdaftar(): void
    {
        $item = collect(NavMenu::semua())->firstWhere('id', 'disiplin');

        $this->assertSame('#', NavMenu::href($item));
    }
}
