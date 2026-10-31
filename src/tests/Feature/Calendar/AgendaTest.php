<?php

namespace Tests\Feature\Calendar;

use App\Contracts\CalendarAgenda;
use App\Contracts\CalendarApi;
use App\Models\User;
use App\Services\Google\CalendarApi as GoogleCalendarApi;
use App\Services\Google\GoogleCalendarAgenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** 今日の予定（自分のカレンダーを読むだけ・時刻順・アプリの予定は除く・数分覚える・失敗やつないでいないときは空） */
class AgendaTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** 今日の予定の窓口に、本物（Google から読む係）が結ばれていることを確かめる */
    public function test_the_real_agenda_is_bound(): void
    {
        $this->assertInstanceOf(GoogleCalendarAgenda::class, app(CalendarAgenda::class));
    }

    /** 終日の予定が先、そのあと時刻の順に、東京の HH:MM で返り、アプリが書いた予定（タスク）は入らないことを確かめる */
    public function test_events_are_sorted_and_app_events_are_excluded(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->agenda = [
            ['summary' => '会議', 'start' => ['dateTime' => '2026-10-31T05:00:00Z'], 'end' => ['dateTime' => '2026-10-31T06:00:00Z']],
            ['summary' => '朝の散歩', 'start' => ['dateTime' => '2026-10-31T07:30:00+09:00'], 'end' => ['dateTime' => '2026-10-31T08:00:00+09:00']],
            ['summary' => '推理手帳のタスク', 'start' => ['date' => '2026-10-31'], 'extendedProperties' => ['private' => ['suiri_task_id' => '5']]],
            ['summary' => '文化の日の準備', 'start' => ['date' => '2026-10-31'], 'end' => ['date' => '2026-11-01']],
        ];

        $items = app(CalendarAgenda::class)->eventsOn($user, '2026-10-31');

        $this->assertSame([
            ['title' => '文化の日の準備', 'starts_at' => null, 'ends_at' => null, 'all_day' => true],
            ['title' => '朝の散歩', 'starts_at' => '07:30', 'ends_at' => '08:00', 'all_day' => false],
            ['title' => '会議', 'starts_at' => '14:00', 'ends_at' => '15:00', 'all_day' => false],
        ], $items);
    }

    /** 前の日から続く予定は 00:00 から、題名のない予定は決まった言葉で出ることを確かめる */
    public function test_overnight_and_untitled_events(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->agenda = [
            ['start' => ['dateTime' => '2026-10-30T23:00:00+09:00'], 'end' => ['dateTime' => '2026-11-01T01:00:00+09:00']],
        ];

        $items = app(CalendarAgenda::class)->eventsOn($user, '2026-10-31');

        $this->assertSame([['title' => config('suiri.calendar.untitled'), 'starts_at' => '00:00', 'ends_at' => null, 'all_day' => false]], $items);
    }

    /** 読んだ予定は数分覚えておき、2回目は Google に聞かないことを確かめる */
    public function test_events_are_cached_for_a_few_minutes(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->agenda = [['summary' => '歯医者', 'start' => ['date' => '2026-10-31']]];

        app(CalendarAgenda::class)->eventsOn($user, '2026-10-31');
        app(CalendarAgenda::class)->eventsOn($user, '2026-10-31');

        $this->assertSame(1, $this->fakeApi()->calls);
    }

    /** つないでいない・切れた・予定を読む許可がない人は、Google に聞かずに空を返すことを確かめる */
    public function test_no_connection_means_no_events(): void
    {
        $broken = $this->connectedUser(['status' => 'broken']);
        $writeOnly = $this->connectedUser(['scopes' => [config('suiri.calendar.scopes')[0]]]);

        $this->assertSame([], app(CalendarAgenda::class)->eventsOn(User::factory()->create(), '2026-10-31'));
        $this->assertSame([], app(CalendarAgenda::class)->eventsOn($broken, '2026-10-31'));
        $this->assertSame([], app(CalendarAgenda::class)->eventsOn($writeOnly, '2026-10-31'));
        $this->assertSame(0, $this->fakeApi()->calls);
    }

    /** Google が止まっていたら、今日の画面を止めずに空を返すことを確かめる */
    public function test_a_failure_returns_no_events(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->failing = true;

        $this->assertSame([], app(CalendarAgenda::class)->eventsOn($user, '2026-10-31'));
    }

    /** 本物の窓口では、ふだんのカレンダー（primary）をその日の0時から次の日の0時まで（東京）読むことを確かめる */
    public function test_the_real_api_reads_the_primary_calendar_for_the_day(): void
    {
        Http::fake(['https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response(['items' => [
            ['summary' => '打ち合わせ', 'start' => ['dateTime' => '2026-10-31T10:00:00+09:00'], 'end' => ['dateTime' => '2026-10-31T11:00:00+09:00']],
        ]])]);
        $user = $this->connectedUser();
        $this->app->instance(CalendarApi::class, app(GoogleCalendarApi::class));

        $items = app(CalendarAgenda::class)->eventsOn($user, '2026-10-31');

        $this->assertSame('打ち合わせ', $items[0]['title']);
        Http::assertSent(fn (Request $request) => $request['timeMin'] === '2026-10-31T00:00:00+09:00'
            && $request['timeMax'] === '2026-11-01T00:00:00+09:00');
    }
}
