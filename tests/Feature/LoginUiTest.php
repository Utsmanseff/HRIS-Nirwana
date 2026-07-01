<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginUiTest extends TestCase
{
    public function test_halaman_login_pakai_layout_auth_dengan_panel_brand(): void
    {
        $res = $this->get('/login');
        $res->assertOk();
        $res->assertSee('auth-brand', false); // kelas panel brand kiri
        $res->assertSee('Satu sistem untuk seluruh kepegawaian'); // tagline brand
    }

    public function test_login_menonjolkan_google_dan_form_nip_password(): void
    {
        $res = $this->get('/login');
        $res->assertSee('Masuk dengan Google');
        $res->assertSee('NIP');
        $res->assertSee('Kata sandi');
        // Teks mockup lama yang salah harus TIDAK ada lagi.
        $res->assertDontSee('Akun dibuat oleh Staff HR');
    }
}
