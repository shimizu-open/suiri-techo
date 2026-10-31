<?php

namespace Tests\Unit\Google;

use App\Enums\CalendarStatus;
use App\Models\CalendarConnection;
use App\Services\Google\Exceptions\GoogleApiFailed;
use App\Services\Google\Exceptions\GoogleAuthBroken;
use App\Services\Google\GoogleTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Google の鍵の係（期限前の取り直し・invalid_grant で切れた扱い・取り消し）。Google の応答は Http::fake の偽物 */
class GoogleTokensTest extends TestCase
{
    use RefreshDatabase;

    /** テストごとに Google の鍵（試し用の値）を入れておく */
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret']);
    }

    /** 期限がまだ先なら、Google に聞かずに今の access token を返すことを確かめる */
    public function test_a_fresh_token_is_used_as_it_is(): void
    {
        Http::fake();
        $connection = CalendarConnection::factory()->create(['access_token' => 'still-good', 'expires_at' => now()->addHour()]);

        $this->assertSame('still-good', app(GoogleTokens::class)->accessToken($connection));
        Http::assertNothingSent();
    }

    /** 期限が近ければ refresh token で取り直し、新しい access token と期限を暗号化して保存することを確かめる */
    public function test_a_token_near_expiry_is_refreshed_and_saved(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'new-access', 'expires_in' => 3599])]);
        $connection = CalendarConnection::factory()->create(['refresh_token' => 'my-refresh', 'expires_at' => now()->addMinute()]);

        $this->assertSame('new-access', app(GoogleTokens::class)->accessToken($connection));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'refresh_token' && $request['refresh_token'] === 'my-refresh'
            && $request['client_id'] === 'test-id' && $request['client_secret'] === 'test-secret');
        $connection->refresh();
        $this->assertSame('new-access', $connection->access_token);
        $this->assertTrue($connection->expires_at->gt(now()->addMinutes(50)));
        $this->assertStringNotContainsString('new-access', (string) DB::table('calendar_connections')->value('access_token'));
    }

    /** invalid_grant（許可が取り消された・切れた）なら、連携を broken にして GoogleAuthBroken を投げることを確かめる */
    public function test_invalid_grant_marks_the_connection_broken(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400)]);
        $connection = CalendarConnection::factory()->create(['expires_at' => now()->subMinute()]);

        // 取り直しを試す。GoogleAuthBroken が出たら、連携が broken になったかを見る
        try {
            app(GoogleTokens::class)->accessToken($connection);
            $this->fail('GoogleAuthBroken が投げられるはず');
        } catch (GoogleAuthBroken) {
            $this->assertSame(CalendarStatus::Broken, $connection->refresh()->status);
        }
    }

    /** Google の不調（500）なら GoogleApiFailed を投げ、連携はつながったままにすることを確かめる（あとで試し直す） */
    public function test_a_server_error_keeps_the_connection_active(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response('oops', 500)]);
        $connection = CalendarConnection::factory()->create(['expires_at' => now()->subMinute()]);

        $this->expectException(GoogleApiFailed::class);
        // 取り直しを試す。失敗のしるしが出ても出なくても、最後に連携がつながったままかを見る
        try {
            app(GoogleTokens::class)->accessToken($connection);
        } finally {
            $this->assertSame(CalendarStatus::Active, $connection->refresh()->status);
        }
    }

    /** 取り消しは refresh token を revoke の住所に送り、うまくいけば true を返すことを確かめる */
    public function test_revoke_sends_the_refresh_token(): void
    {
        Http::fake(['oauth2.googleapis.com/revoke' => Http::response('', 200)]);
        $connection = CalendarConnection::factory()->create(['refresh_token' => 'to-revoke']);

        $this->assertTrue(app(GoogleTokens::class)->revoke($connection));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://oauth2.googleapis.com/revoke' && $request['token'] === 'to-revoke');
    }

    /** 取り消しに失敗しても例外にせず false を返すことを確かめる（つなぐのをやめる・退会は先へ進める） */
    public function test_revoke_failure_returns_false(): void
    {
        Http::fake(['oauth2.googleapis.com/revoke' => Http::response(['error' => 'invalid_token'], 400)]);
        $connection = CalendarConnection::factory()->create();

        $this->assertFalse(app(GoogleTokens::class)->revoke($connection));
    }

    /** Google の鍵が両方そろっているときだけ「設定済み」になることを確かめる */
    public function test_configured_needs_both_keys(): void
    {
        $this->assertTrue(GoogleTokens::configured());
        config(['services.google.client_secret' => '']);
        $this->assertFalse(GoogleTokens::configured());
    }
}
