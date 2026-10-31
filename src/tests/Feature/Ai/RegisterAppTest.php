<?php

namespace Tests\Feature\Ai;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Ai\Concerns\ConnectsAi;
use Tests\TestCase;

/** AI の自己登録（POST /oauth/register）：戻る先は chatgpt.com・claude.ai・claude.com だけ通り、回数制限がある */
class RegisterAppTest extends TestCase
{
    use ConnectsAi, RefreshDatabase;

    /** 許した戻る先（ChatGPT・Claude） */
    public static function allowedRedirects(): array
    {
        return [
            'ChatGPT' => ['https://chatgpt.com/connector/oauth/abc123'],
            'ChatGPT（iss つき）' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
            'Claude' => ['https://claude.ai/api/mcp/auth_callback'],
            'Claude（claude.com）' => ['https://claude.com/api/mcp/auth_callback'],
        ];
    }

    /** 許していない戻る先（よその住所・似せた住所・手元・ユーザー名入り・http） */
    public static function rejectedRedirects(): array
    {
        return [
            'よその住所' => ['https://evil.example/callback'],
            '似せた住所' => ['https://chatgpt.com.evil.example/callback'],
            '手元' => ['http://localhost:3000/callback'],
            'ユーザー名入り' => ['https://chatgpt.com@evil.example/callback'],
            'http' => ['http://chatgpt.com/connector/oauth/abc'],
        ];
    }

    /** ChatGPT・Claude の戻る先なら、登録できて番号が返る */
    #[DataProvider('allowedRedirects')]
    public function test_allowed_redirects_can_register(string $redirect): void
    {
        $this->postJson('/oauth/register', ['name' => 'ChatGPT', 'redirect_uris' => [$redirect]])
            ->assertCreated()
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonPath('redirect_uris', [$redirect])
            ->assertJsonStructure(['client_id']);
    }

    /** よその戻る先は 400（invalid_redirect_uri）で、登録の行もできない */
    #[DataProvider('rejectedRedirects')]
    public function test_other_redirects_are_rejected(string $redirect): void
    {
        $this->postJson('/oauth/register', ['name' => 'Evil', 'redirect_uris' => [$redirect]])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_redirect_uri');

        $this->assertDatabaseCount('oauth_clients', 0);
    }

    /** 同じところから1時間に20回を超えて登録すると 429 で止まる */
    public function test_registration_is_rate_limited(): void
    {
        // 20回までは登録できる
        foreach (range(1, 20) as $i) {
            $this->registerApp();
        }

        $this->postJson('/oauth/register', ['name' => 'ChatGPT', 'redirect_uris' => [self::CHATGPT_CALLBACK]])->assertStatus(429);
    }
}
