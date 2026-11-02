<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * cloudflared meneruskan permintaan HTTPS dari 127.0.0.1, jadi URL asset harus
 * mengikuti skema aslinya supaya tidak diblokir browser sebagai mixed content.
 */
class TrustedProxyTest extends TestCase
{
    private const TUNNEL_URL = 'http://demo.trycloudflare.com/login';

    public function test_assets_use_https_for_a_forwarded_request_from_loopback(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get(self::TUNNEL_URL)
            ->assertOk()
            ->assertSee('https://demo.trycloudflare.com/images/logo.png', false);
    }

    public function test_forwarded_scheme_from_another_client_is_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10', 'HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get(self::TUNNEL_URL)
            ->assertOk()
            ->assertSee('http://demo.trycloudflare.com/images/logo.png', false);
    }
}
