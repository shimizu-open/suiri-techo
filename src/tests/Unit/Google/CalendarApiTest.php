<?php

namespace Tests\Unit\Google;

use App\Models\CalendarConnection;
use App\Services\Google\CalendarApi;
use App\Services\Google\Exceptions\GoogleApiFailed;
use App\Services\Google\Exceptions\SyncTokenExpired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Google カレンダーの REST を呼ぶ本物の係（住所・送る中身・失敗の扱い）。Google の応答は Http::fake の偽物 */
class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://www.googleapis.com/calendar/v3';

    private const CAL = 'abc@group.calendar.google.com';

    private CalendarConnection $connection;

    /** テストごとに、期限がまだ先のトークンを持つ連携を用意する（トークンの取り直しは起きない） */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = CalendarConnection::factory()->create(['access_token' => 'tok', 'expires_at' => now()->addHour()]);
    }

    /** 本物の係を返す（テストで結ばれている偽物ではなく） */
    private function api(): CalendarApi
    {
        return app(CalendarApi::class);
    }

    /** 専用カレンダーを作るとき、名前とタイムゾーンだけを access token つきで送り、返った ID を返すことを確かめる */
    public function test_insert_calendar_posts_name_and_time_zone(): void
    {
        Http::fake([self::BASE.'/calendars' => Http::response(['id' => self::CAL])]);

        $this->assertSame(self::CAL, $this->api()->insertCalendar($this->connection, '推理手帳', 'Asia/Tokyo'));
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer tok')
            && $request->data() === ['summary' => '推理手帳', 'timeZone' => 'Asia/Tokyo']);
    }

    /** 予定は id つきで作り、同じ id がもうあれば（409）PUT で置き換えることを確かめる（二重登録しない） */
    public function test_upsert_event_inserts_then_updates_on_conflict(): void
    {
        $events = self::BASE.'/calendars/'.rawurlencode(self::CAL).'/events';
        Http::fake([
            $events => Http::response(['error' => ['code' => 409]], 409),
            $events.'/e1' => Http::response(['id' => 'e1']),
        ]);

        $this->api()->upsertEvent($this->connection, self::CAL, 'e1', ['summary' => '牛乳を買う']);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request->url() === $events
            && $request['id'] === 'e1' && $request['summary'] === '牛乳を買う');
        Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && $request->url() === $events.'/e1'
            && $request->data() === ['summary' => '牛乳を買う']);
    }

    /** もう消えている予定・カレンダーを消すとき（404・410）は、失敗にしないことを確かめる */
    public function test_deleting_something_already_gone_is_fine(): void
    {
        Http::fake([
            self::BASE.'/calendars/*/events/gone' => Http::response('', 410),
            self::BASE.'/calendars/*' => Http::response('', 404),
        ]);

        $this->api()->deleteEvent($this->connection, self::CAL, 'gone');
        $this->api()->deleteCalendar($this->connection, self::CAL);
        $this->assertFalse($this->api()->hasCalendar($this->connection, self::CAL));
        Http::assertSentCount(3);
    }

    /** Google の不調（503）なら GoogleApiFailed を投げることを確かめる（後回しの仕事が試し直す） */
    public function test_a_server_error_throws(): void
    {
        Http::fake([self::BASE.'/*' => Http::response('', 503)]);

        $this->expectException(GoogleApiFailed::class);
        $this->api()->upsertEvent($this->connection, self::CAL, 'e1', ['summary' => 'x']);
    }

    /** 401 なら access token を期限切れにしてから失敗にすることを確かめる（次で取り直す） */
    public function test_unauthorized_expires_the_access_token(): void
    {
        Http::fake([self::BASE.'/*' => Http::response('', 401)]);

        // 予定を消してみる。失敗のしるしが出たら、トークンが期限切れになったかを見る
        try {
            $this->api()->deleteEvent($this->connection, self::CAL, 'e1');
            $this->fail('GoogleApiFailed が投げられるはず');
        } catch (GoogleApiFailed $e) {
            $this->assertSame(401, $e->status);
            $this->assertTrue($this->connection->refresh()->expires_at->isPast());
        }
    }

    /** 差分の取り込みは印を付けて、ページを最後までたどり、最後のページの nextSyncToken を返すことを確かめる */
    public function test_list_changed_events_follows_pages_and_returns_the_next_token(): void
    {
        Http::fake([self::BASE.'/calendars/*/events*' => Http::sequence()
            ->push(['items' => [['id' => 'a']], 'nextPageToken' => 'p2'])
            ->push(['items' => [['id' => 'b']], 'nextSyncToken' => 'sync-2'])]);

        $page = $this->api()->listChangedEvents($this->connection, self::CAL, 'sync-1');

        $this->assertSame(['a', 'b'], array_column($page['events'], 'id'));
        $this->assertSame('sync-2', $page['sync_token']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'syncToken=sync-1') && str_contains($request->url(), 'pageToken=p2'));
    }

    /** 印が古くなった（410）ら SyncTokenExpired を投げることを確かめる（全件を取り直す合図） */
    public function test_an_expired_sync_token_signals_a_full_sync(): void
    {
        Http::fake([self::BASE.'/calendars/*/events*' => Http::response(['error' => ['code' => 410, 'message' => 'fullSyncRequired']], 410)]);

        $this->expectException(SyncTokenExpired::class);
        $this->api()->listChangedEvents($this->connection, self::CAL, 'old');
    }

    /** 今日の予定は primary を時間の範囲で、繰り返しを1回ずつに分けて開始の順に読み、受け取る項目を題名と日時に絞ることを確かめる */
    public function test_list_events_reads_a_time_range(): void
    {
        Http::fake([self::BASE.'/calendars/primary/events*' => Http::response(['items' => [['id' => 'x', 'summary' => '歯医者']]])]);

        $items = $this->api()->listEvents($this->connection, 'primary', '2026-10-31T00:00:00+09:00', '2026-11-01T00:00:00+09:00');

        $this->assertSame('歯医者', $items[0]['summary']);
        Http::assertSent(fn (Request $request) => $request['singleEvents'] === 'true' && $request['orderBy'] === 'startTime'
            && $request['timeMin'] === '2026-10-31T00:00:00+09:00' && $request['timeMax'] === '2026-11-01T00:00:00+09:00'
            && $request['fields'] === config('suiri.calendar.agenda_fields'));
    }
}
