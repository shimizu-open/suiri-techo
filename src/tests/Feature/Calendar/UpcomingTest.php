<?php

namespace Tests\Feature\Calendar;

use App\Contracts\CalendarAgenda;
use App\Contracts\CalendarApi;
use App\Models\User;
use App\Services\Google\CalendarApi as GoogleCalendarApi;
use App\Services\Google\NullCalendarAgenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** これからの予定（確かめる日の提案 S-4 に使う）：1回の問い合わせ・日付と時刻の順・始まった予定とアプリの予定は除く・数分覚える・失敗やつないでいないときは空 */
class UpcomingTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** 時計を 2026-10-31 15:00（東京）に止める */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('Asia/Tokyo')->setDate(2026, 10, 31)->setTime(15, 0));
    }

    /** 日付 → 終日 → 時刻の順に並び、もう始まった予定・アプリが書いた予定（タスク）は入らないことを確かめる */
    public function test_upcoming_events_are_sorted_and_filtered(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->agenda = [
            ['summary' => '体育', 'start' => ['dateTime' => '2026-11-04T10:40:00+09:00'], 'end' => ['dateTime' => '2026-11-04T11:30:00+09:00']],
            ['summary' => 'さっきの会議', 'start' => ['dateTime' => '2026-10-31T14:00:00+09:00'], 'end' => ['dateTime' => '2026-10-31T16:00:00+09:00']],
            ['summary' => '夕方の打ち合わせ', 'start' => ['dateTime' => '2026-10-31T08:00:00Z'], 'end' => ['dateTime' => '2026-10-31T09:00:00Z']],
            ['summary' => '推理手帳のタスク', 'start' => ['date' => '2026-11-01'], 'extendedProperties' => ['private' => ['suiri_task_id' => '5']]],
            ['summary' => '文化の日', 'start' => ['date' => '2026-11-03'], 'end' => ['date' => '2026-11-04']],
        ];

        $items = app(CalendarAgenda::class)->upcoming($user, 14);

        $this->assertSame([
            ['title' => '夕方の打ち合わせ', 'date' => '2026-10-31', 'starts_at' => '17:00', 'all_day' => false],
            ['title' => '文化の日', 'date' => '2026-11-03', 'starts_at' => null, 'all_day' => true],
            ['title' => '体育', 'date' => '2026-11-04', 'starts_at' => '10:40', 'all_day' => false],
        ], $items);
    }

    /** 読んだ予定は数分覚えておき、2回目は Google に聞かないことを確かめる */
    public function test_upcoming_events_are_cached(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->agenda = [['summary' => '体育', 'start' => ['date' => '2026-11-04']]];

        app(CalendarAgenda::class)->upcoming($user, 14);
        app(CalendarAgenda::class)->upcoming($user, 14);

        $this->assertSame(1, $this->fakeApi()->calls);
    }

    /** 覚えておいた予定から返すときも、覚えている間に始まった予定は外れ、終日の予定とまだの予定は残ることを確かめる */
    public function test_events_that_started_while_cached_are_dropped(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->agenda = [
            ['summary' => 'もうすぐの会議', 'start' => ['dateTime' => '2026-10-31T15:03:00+09:00'], 'end' => ['dateTime' => '2026-10-31T16:00:00+09:00']],
            ['summary' => '夕方の打ち合わせ', 'start' => ['dateTime' => '2026-10-31T17:00:00+09:00'], 'end' => ['dateTime' => '2026-10-31T18:00:00+09:00']],
            ['summary' => '文化の日', 'start' => ['date' => '2026-11-03'], 'end' => ['date' => '2026-11-04']],
        ];
        $this->assertSame(['もうすぐの会議', '夕方の打ち合わせ', '文化の日'], array_column(app(CalendarAgenda::class)->upcoming($user, 14), 'title'));

        $this->travel(4)->minutes();

        $this->assertSame(['夕方の打ち合わせ', '文化の日'], array_column(app(CalendarAgenda::class)->upcoming($user, 14), 'title'));
        $this->assertSame(1, $this->fakeApi()->calls);
    }

    /** つないでいない・切れた人は Google に聞かずに空、Google が止まっていたら空（画面を止めない）ことを確かめる */
    public function test_no_connection_or_failure_means_no_events(): void
    {
        $broken = $this->connectedUser(['status' => 'broken']);

        $this->assertSame([], app(CalendarAgenda::class)->upcoming(User::factory()->create(), 14));
        $this->assertSame([], app(CalendarAgenda::class)->upcoming($broken, 14));
        $this->assertSame(0, $this->fakeApi()->calls);

        $user = $this->connectedUser();
        $this->fakeApi()->failing = true;
        $this->assertSame([], app(CalendarAgenda::class)->upcoming($user, 14));
    }

    /** つないでいないときの仮の窓口（Null）も、これからの予定を空で返すことを確かめる */
    public function test_the_null_agenda_returns_nothing(): void
    {
        $this->assertSame([], (new NullCalendarAgenda)->upcoming(User::factory()->create(), 14));
    }

    /** 本物の窓口では、ふだんのカレンダーを、いまから14日先の終わりまで1回だけ、題名と日時の項目に絞って読むことを確かめる */
    public function test_the_real_api_reads_the_next_days_in_one_request(): void
    {
        Http::fake(['https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response(['items' => [
            ['summary' => '体育', 'start' => ['dateTime' => '2026-11-04T10:40:00+09:00'], 'end' => ['dateTime' => '2026-11-04T11:30:00+09:00']],
        ]])]);
        $user = $this->connectedUser();
        $this->app->instance(CalendarApi::class, app(GoogleCalendarApi::class));

        $items = app(CalendarAgenda::class)->upcoming($user, 14);

        $this->assertSame([['title' => '体育', 'date' => '2026-11-04', 'starts_at' => '10:40', 'all_day' => false]], $items);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['timeMin'] === '2026-10-31T15:00:00+09:00'
            && $request['timeMax'] === '2026-11-15T00:00:00+09:00'
            && $request['singleEvents'] === 'true'
            && $request['fields'] === config('suiri.calendar.agenda_fields'));
    }
}
