<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginGoogleIconTest extends TestCase
{
    public function test_tombol_google_render_logo_4_warna(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('#4285F4', false)   // biru khas logo Google
            ->assertSee('#EA4335', false);  // merah khas logo Google
    }
}
