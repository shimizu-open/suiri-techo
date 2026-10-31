<?php

namespace Tests\Feature\Ai;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ai\Concerns\ConnectsAi;
use Tests\TestCase;

/** 同意の画面（/oauth/authorize）：ログインと利用規約の同意を先に確かめ、AI の名前・戻る先・見せる範囲を出して、許す／やめるを返す */
class AuthorizeTest extends TestCase
{
    use ConnectsAi, RefreshDatabase;

    /** テストごとの準備：テスト用の鍵を入れる（同意の受付が通行証を出す係を作るのに要る） */
    protected function setUp(): void
    {
        parent::setUp();
        $this->usePassportKeys();
    }

    /** ログインしていない人は Google のログインへ送り、ログインのあとに戻れるよう住所を覚えておく */
    public function test_guest_is_sent_to_google_login_and_url_is_remembered(): void
    {
        $url = $this->authorizeUrl($this->registerApp(), $this->pkce()[1]);

        $this->get($url)->assertRedirect(route('auth.google'));
        // 覚えた住所は、同意の画面の住所と同じ（問いの並びは Laravel がそろえ直すので、中身で比べる）
        parse_str((string) parse_url($url, PHP_URL_QUERY), $sent);
        parse_str((string) parse_url(session('url.intended'), PHP_URL_QUERY), $remembered);
        $this->assertSame(url('/oauth/authorize'), strtok(session('url.intended'), '?'));
        $this->assertEquals($sent, $remembered);
    }

    /** 利用規約にまだ同意していない人は、同意の画面（/agree）へ送る（許可は出させない） */
    public function test_unagreed_user_is_sent_to_agreement_first(): void
    {
        $url = $this->authorizeUrl($this->registerApp(), $this->pkce()[1]);

        $this->actingAs(User::factory()->unagreed()->create())->get($url)->assertRedirect(route('agree.show'));
        $this->assertDatabaseCount('oauth_auth_codes', 0);
    }

    /** 同意の画面から /agree へ送られた人は、利用規約に同意したあと、もとの同意の画面へ戻る */
    public function test_agreement_returns_to_consent_screen(): void
    {
        $url = $this->authorizeUrl($this->registerApp(), $this->pkce()[1]);
        $user = User::factory()->unagreed()->create();
        $this->actingAs($user)->get($url)->assertRedirect(route('agree.show'));

        $back = $this->post(route('agree.store'), ['accept_terms' => '1', 'confirm_age' => '1'])->headers->get('Location');

        $this->assertSame(url('/oauth/authorize'), strtok($back, '?'));
        $this->get($back)->assertOk()->assertSee('許可する');
    }

    /** 同意済みの人には、AI の名前・戻る先・仕事の場面は見せないこと・行き先の会社を出す。ほかのサイトに埋め込ませない */
    public function test_consent_screen_shows_app_host_and_scope(): void
    {
        $url = $this->authorizeUrl($this->registerApp(), $this->pkce()[1]);

        $this->actingAs(User::factory()->create())->get($url)
            ->assertOk()
            ->assertSee('ChatGPT に、あなたの事件の記録（仕事の場面を除く）を読ませてよいですか？')
            ->assertSee('chatgpt.com')
            ->assertSee('書き換えはできません')
            ->assertSee('OpenAI（米国）')
            ->assertSee('許可する')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    /** 「やめる」を押すと、AI の戻る先へ access_denied を返し、引換券は出さない */
    public function test_deny_returns_access_denied(): void
    {
        $url = $this->authorizeUrl($this->registerApp(), $this->pkce()[1]);
        $user = User::factory()->create();
        $authToken = $this->actingAs($user)->get($url)->assertOk()->viewData('authToken');

        $location = $this->delete('/oauth/authorize', ['auth_token' => $authToken])->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith(self::CHATGPT_CALLBACK.'?', $location);
        $this->assertStringContainsString('error=access_denied', $location);
        $this->assertDatabaseCount('oauth_auth_codes', 0);
    }

    /** 許す → 引換券を PKCE の合言葉つきで通行証（1時間）と更新の札に引き換え → 窓口を読める → 設定で取り消すと 401 */
    public function test_full_flow_from_consent_to_mcp_and_revoke(): void
    {
        $appId = $this->registerApp();
        [$verifier, $challenge] = $this->pkce();
        $user = User::factory()->create();
        $authToken = $this->actingAs($user)->get($this->authorizeUrl($appId, $challenge))->viewData('authToken');

        $location = $this->post('/oauth/authorize', ['auth_token' => $authToken])->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('state-123', $query['state']);

        $tokens = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $appId,
            'redirect_uri' => self::CHATGPT_CALLBACK,
            'code_verifier' => $verifier,
            'code' => $query['code'],
            'resource' => url('/mcp'),
        ])->assertOk()->assertJsonPath('expires_in', 3600)->json();
        $this->assertNotEmpty($tokens['refresh_token']);

        $this->app['auth']->forgetGuards(); // 次の要求は、ログインの Cookie ではなく通行証だけで入る
        $this->callMcp('tools/list', [], ['Authorization' => 'Bearer '.$tokens['access_token']])->assertOk();

        $this->actingAs($user)->delete(route('settings.ai.revoke', $appId))->assertRedirect(route('settings.show'));
        $this->assertDatabaseMissing('oauth_access_tokens', ['user_id' => $user->id, 'revoked' => false]);

        $this->app['auth']->forgetGuards();
        $this->callMcp('tools/list', [], ['Authorization' => 'Bearer '.$tokens['access_token']])->assertUnauthorized();
    }

    /** PKCE の合言葉がちがうと、引換券を通行証に引き換えられない */
    public function test_wrong_code_verifier_is_rejected(): void
    {
        $appId = $this->registerApp();
        $user = User::factory()->create();
        $authToken = $this->actingAs($user)->get($this->authorizeUrl($appId, $this->pkce()[1]))->viewData('authToken');
        $location = $this->post('/oauth/authorize', ['auth_token' => $authToken])->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->post('/oauth/token', [
            'grant_type' => 'authorization_code', 'client_id' => $appId, 'redirect_uri' => self::CHATGPT_CALLBACK,
            'code_verifier' => $this->pkce()[0], 'code' => $query['code'],
        ])->assertStatus(400);
    }
}
