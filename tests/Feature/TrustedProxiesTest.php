<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    public function test_proxy_https_headers_generate_https_asset_urls(): void
    {
        $response = $this->withHeaders([
            'Host' => 'ejemplo.ngrok-free.dev',
            'X-Forwarded-Host' => 'ejemplo.ngrok-free.dev',
            'X-Forwarded-Proto' => 'https',
        ])->get('/');

        $response->assertStatus(200);
        $response->assertSee('https://ejemplo.ngrok-free.dev/img/logo_app.png', false);
        $response->assertDontSee('http://ejemplo.ngrok-free.dev/img/logo_app.png', false);
        $response->assertDontSee('http://ejemplo.ngrok-free.dev/build/assets/', false);
    }

    public function test_direct_http_request_keeps_http_asset_urls(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('https://localhost/img/logo_app.png', false);
    }
}
