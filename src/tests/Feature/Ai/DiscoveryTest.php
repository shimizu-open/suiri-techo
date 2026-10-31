<?php

namespace Tests\Feature\Ai;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** AI が最初に読む案内の住所（/.well-known/…）と、窓口の GET が 405 になること */
class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    /** 窓口の案内（PRM）が、窓口の住所（/mcp）と通行証を出す係の住所を返す（Claude は resource の完全一致を求める） */
    public function test_protected_resource_metadata_points_to_mcp(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp'))
            ->assertJsonPath('authorization_servers', [url('/')])
            ->assertJsonPath('scopes_supported', ['mcp:use']);
    }

    /** 通行証を出す係の案内が、同意の画面・通行証・自己登録の住所と S256 を返し、openid などは載せない */
    public function test_authorization_server_metadata_lists_endpoints(): void
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server')->assertOk()
            ->assertJsonPath('issuer', url('/'))
            ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
            ->assertJsonPath('token_endpoint', url('/oauth/token'))
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            ->assertJsonPath('code_challenge_methods_supported', ['S256']);

        $this->assertSame(['mcp:use'], $response->json('scopes_supported'));
        $this->assertContains('none', $response->json('token_endpoint_auth_methods_supported'));
    }

    /** 窓口は POST だけ。GET（SSE を開く）と DELETE は 405 */
    public function test_mcp_accepts_post_only(): void
    {
        $this->get('/mcp')->assertStatus(405)->assertHeader('Allow', 'POST');
        $this->delete('/mcp')->assertStatus(405);
    }
}
