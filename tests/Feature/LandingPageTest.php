<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_shows_talentra_sections(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Talentra');
        $response->assertSee('Belajar Terarah');
        $response->assertSee('Coba Gratis Sekarang');
        $response->assertSee('Semua Akses');
        $response->assertSee('Masuk');
    }
}
