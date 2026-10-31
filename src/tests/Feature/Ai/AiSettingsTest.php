<?php

namespace Tests\Feature\Ai;

use App\Models\User;
use App\Queries\Ai\ConnectedAppsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Ai\Concerns\ConnectsAi;
use Tests\TestCase;

/** 設定の「ChatGPT・Claude とつなぐ」：つないでいる AI の一覧（本人の分だけ）と「つなぐのをやめる」 */
class AiSettingsTest extends TestCase
{
    use ConnectsAi, RefreshDatabase;

    /** 一覧には、その人が今つないでいる AI だけが出る（他人の AI・取り消したもの・札も切れたものは出ない） */
    public function test_connected_apps_are_own_and_active_only(): void
    {
        $user = User::factory()->create();
        $chatgpt = $this->registerApp(self::CHATGPT_CALLBACK, 'ChatGPT');
        $claude = $this->registerApp('https://claude.ai/api/mcp/auth_callback', 'Claude');
        $old = $this->registerApp(self::CHATGPT_CALLBACK, 'Old');
        $this->grantToken($user->id, $chatgpt);
        $this->grantToken($user->id, $claude, expiresInMinutes: -120); // 通行証は切れたが、更新の札は生きている
        $this->grantToken($user->id, $old, expiresInMinutes: -120, refreshDays: null); // どちらも切れた
        $this->grantToken(User::factory()->create()->id, $this->registerApp(self::CHATGPT_CALLBACK, 'Others'));

        $apps = collect(app(ConnectedAppsQuery::class)->for($user));

        $this->assertEqualsCanonicalizing(['ChatGPT', 'Claude'], $apps->pluck('name')->all());
        $this->assertSame('claude.ai', $apps->firstWhere('name', 'Claude')['host']);
    }

    /** 設定の枠に、つないでいる AI の名前と「つなぐのをやめる」、窓口の住所とつなぎ方が出る */
    public function test_panel_shows_apps_address_and_steps(): void
    {
        $user = User::factory()->create();
        $this->grantToken($user->id, $this->registerApp());

        $this->blade('<x-settings.ai-panel :apps="$apps" />', ['apps' => app(ConnectedAppsQuery::class)->for($user)])
            ->assertSee('ChatGPT・Claude とつなぐ')
            ->assertSee('ChatGPT')
            ->assertSee('chatgpt.com')
            ->assertSee('つなぐのをやめる')
            ->assertSee(url('/mcp'))
            ->assertSee('Developer mode')
            ->assertSee('画面の名前（英語の項目名）は、変わることがあるよ。');

        $this->blade('<x-settings.ai-panel :apps="[]" />')->assertSee('つないでいません')->assertDontSee('つなぐのをやめる');
    }

    /** 設定の画面に「ChatGPT・Claude とつなぐ」の枠が出る。まだつないでいなければ「つないでいません」で、やめるボタンは出ない */
    public function test_settings_page_shows_ai_panel(): void
    {
        $this->actingAs(User::factory()->create())->get(route('settings.show'))->assertOk()
            ->assertSee('ChatGPT・Claude とつなぐ')
            ->assertSee('つないでいません')
            ->assertSee(url('/mcp'))
            ->assertDontSee('つなぐのをやめる');
    }

    /** 設定の画面に、自分がつないだ AI の名前と戻る先が出る。他人がつないだ AI の名前は出ない */
    public function test_settings_page_lists_own_connected_apps(): void
    {
        $user = User::factory()->create();
        $this->grantToken($user->id, $this->registerApp('https://claude.ai/api/mcp/auth_callback', 'Claude'));
        $this->grantToken(User::factory()->create()->id, $this->registerApp(self::CHATGPT_CALLBACK, 'OthersAssistant'));

        $this->actingAs($user)->get(route('settings.show'))->assertOk()
            ->assertSee('つないでいる AI')
            ->assertSee('Claude')
            ->assertSee('claude.ai')
            ->assertSee('つなぐのをやめる')
            ->assertSee(route('settings.ai.revoke', app(ConnectedAppsQuery::class)->for($user)[0]['id']), false)
            ->assertDontSee('OthersAssistant');
    }

    /** 設定の画面から「つなぐのをやめる」を押すと、戻った設定の画面にやめた一言が出て、その AI の名前は一覧から消える */
    public function test_revoke_from_settings_page_removes_app(): void
    {
        $user = User::factory()->create();
        $appId = $this->registerApp(self::CHATGPT_CALLBACK, 'MyChatGPT');
        $this->grantToken($user->id, $appId);

        $this->actingAs($user)->get(route('settings.show'))->assertSee('MyChatGPT');
        $this->followingRedirects()->delete(route('settings.ai.revoke', $appId))->assertOk()
            ->assertSee('MyChatGPT とつなぐのをやめたよ。')
            ->assertSee('つないでいません')
            ->assertDontSee('つなぐのをやめる');

        $this->get(route('settings.show'))->assertDontSee('MyChatGPT');
        $this->delete(route('settings.ai.revoke', $appId))->assertNotFound();
    }

    /** 「つなぐのをやめる」で、その AI の通行証・更新の札・引換券がすべて取り消され、設定の画面へ戻る */
    public function test_revoke_marks_tokens_revoked(): void
    {
        $user = User::factory()->create();
        $appId = $this->registerApp();
        $tokenId = $this->grantToken($user->id, $appId);
        $othersToken = $this->grantToken(User::factory()->create()->id, $appId);

        $this->actingAs($user)->delete(route('settings.ai.revoke', $appId))
            ->assertRedirect(route('settings.show'))
            ->assertSessionHas('status', 'ChatGPT とつなぐのをやめたよ。');

        $this->assertTrue((bool) DB::table('oauth_access_tokens')->where('id', $tokenId)->value('revoked'));
        $this->assertTrue((bool) DB::table('oauth_refresh_tokens')->where('access_token_id', $tokenId)->value('revoked'));
        $this->assertFalse((bool) DB::table('oauth_access_tokens')->where('id', $othersToken)->value('revoked'));
        $this->assertSame([], app(ConnectedAppsQuery::class)->for($user));
    }

    /** 他人だけがつないでいる AI・ない番号を取り消そうとすると 404（他人の通行証には触れない） */
    public function test_cannot_revoke_others_app(): void
    {
        $appId = $this->registerApp();
        $othersToken = $this->grantToken(User::factory()->create()->id, $appId);

        $this->actingAs(User::factory()->create())->delete(route('settings.ai.revoke', $appId))->assertNotFound();
        $this->delete(route('settings.ai.revoke', '00000000-0000-0000-0000-000000000000'))->assertNotFound();
        $this->assertFalse((bool) DB::table('oauth_access_tokens')->where('id', $othersToken)->value('revoked'));
    }
}
