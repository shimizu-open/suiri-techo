<?php

namespace Tests\Feature\Calendar;

use App\Contracts\CalendarApi;
use App\Jobs\PullCalendarChanges;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Models\User;
use App\Services\Google\CalendarApi as GoogleCalendarApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/**
 * 取り込みで、消えた予定とのつながりを外す（ID だけの削除の知らせ・410 のあとの全件の読み直し）と、
 * 確かめるタスクの予定を動かされたら予想の確かめる日も合わせること。Google の応答は Http::fake の偽物（本物の窓口を通す）
 */
class PullRemovedEventsTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    private const EVENTS = 'https://www.googleapis.com/calendar/v3/calendars/*/events*';

    /** テストごとに、カレンダーの窓口を本物（REST を呼ぶ係）にする。Google への問い合わせは Http::fake で受ける */
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(CalendarApi::class, $this->app->make(GoogleCalendarApi::class));
    }

    /** カレンダーとつないだ利用者を作る（偽の窓口は使わないので、専用カレンダーは連携の行に入っているだけ） */
    private function connectedUser(array $attributes = []): User
    {
        return CalendarConnection::factory()->create($attributes)->user;
    }

    /** その人の、予定とつながったタスクを作る（書き出しが済んだ日時も入れられる） */
    private function linkedTask(User $user, string $eventId, ?string $syncedAt = null): Task
    {
        $task = Task::factory()->for($user)->create(['due_on' => '2026-11-02']);
        $task->forceFill(['calendar_event_id' => $eventId, 'calendar_synced_at' => $syncedAt])->saveQuietly();

        return $task;
    }

    /** その人の取り込みをその場で動かす */
    private function pull(User $user): void
    {
        app()->call([new PullCalendarChanges($user->calendarConnection->id), 'handle']);
    }

    /** 消された予定が ID と cancelled だけで届いても、その人のタスクのつながりを外し（タスクは残す）、他人のタスクは触らないことを確かめる */
    public function test_an_id_only_cancelled_notice_unlinks_the_task(): void
    {
        $user = $this->connectedUser(['sync_token' => 'sync-0']);
        $task = $this->linkedTask($user, 'ev1', now()->subDay()->toDateTimeString());
        $others = $this->linkedTask(User::factory()->create(), 'ev1');
        Http::fake([self::EVENTS => Http::response(['items' => [['id' => 'ev1', 'status' => 'cancelled']], 'nextSyncToken' => 'sync-1'])]);

        $this->pull($user);

        $this->assertModelExists($task);
        $this->assertNull($task->refresh()->calendar_event_id);
        $this->assertNull($task->calendar_synced_at);
        $this->assertSame('ev1', $others->refresh()->calendar_event_id);
        $this->assertSame('sync-1', $user->calendarConnection->refresh()->sync_token);
    }

    /**
     * 印が切れた（410）あとの全件の読み直しで、ページをまたいで集めた予定の ID とタスクを比べ、Google にない予定とのつながりだけを外し、
     * 新しい印を保存することを確かめる（2ページ目にある予定・まだ書き出していないタスク・他人のタスクは残す）
     */
    public function test_a_full_sync_after_410_unlinks_events_missing_from_google(): void
    {
        $user = $this->connectedUser(['sync_token' => 'old']);
        $earlier = now()->subHour()->toDateTimeString();
        $onPage1 = $this->linkedTask($user, 'keep1', $earlier);
        $onPage2 = $this->linkedTask($user, 'keep2', $earlier);
        $gone = $this->linkedTask($user, 'gone', $earlier);
        $notPushedYet = $this->linkedTask($user, 'fresh');
        $others = $this->linkedTask(User::factory()->create(), 'gone', $earlier);
        Http::fake([self::EVENTS => Http::sequence()
            ->push(['error' => ['code' => 410, 'message' => 'fullSyncRequired']], 410)
            ->push(['items' => [['id' => 'keep1', 'status' => 'confirmed']], 'nextPageToken' => 'p2'])
            ->push(['items' => [['id' => 'keep2', 'status' => 'confirmed'], ['id' => 'other', 'status' => 'cancelled']], 'nextSyncToken' => 'sync-new'])]);

        $this->pull($user);

        $this->assertNull($gone->refresh()->calendar_event_id);
        $this->assertModelExists($gone);
        $this->assertSame('keep1', $onPage1->refresh()->calendar_event_id);
        $this->assertSame('keep2', $onPage2->refresh()->calendar_event_id);
        $this->assertSame('fresh', $notPushedYet->refresh()->calendar_event_id);
        $this->assertSame('gone', $others->refresh()->calendar_event_id);
        $this->assertSame('sync-new', $user->calendarConnection->refresh()->sync_token);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'pageToken=p2') && ! str_contains($request->url(), 'syncToken'));
    }

    /** 差分の取り込み（印が生きている）では、届かなかった予定のつながりは外さないことを確かめる（全件のときだけ比べる） */
    public function test_an_incremental_sync_keeps_unmentioned_links(): void
    {
        $user = $this->connectedUser(['sync_token' => 'sync-0']);
        $task = $this->linkedTask($user, 'ev1', now()->subHour()->toDateTimeString());
        Http::fake([self::EVENTS => Http::response(['items' => [], 'nextSyncToken' => 'sync-1'])]);

        $this->pull($user);

        $this->assertSame('ev1', $task->refresh()->calendar_event_id);
    }

    /** 確かめるタスクの予定を専用カレンダーで動かされたら、タスクの期日・時刻と一緒に、予想の確かめる日・時刻も合わせることを確かめる（文と確かさは変えない） */
    public function test_moving_a_verify_event_moves_the_prediction_check(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        CalendarConnection::factory()->for($user)->create(['sync_token' => 'sync-0']);
        $task = $prediction->task;
        $task->forceFill(['calendar_event_id' => 'ev9'])->saveQuietly();
        $day = today()->addDays(2)->toDateString();
        Http::fake([self::EVENTS => Http::response(['nextSyncToken' => 'sync-1', 'items' => [[
            'id' => 'ev9', 'status' => 'confirmed', 'extendedProperties' => ['private' => ['suiri_task_id' => (string) $task->id]],
            'start' => ['dateTime' => $day.'T07:30:00+09:00'], 'end' => ['dateTime' => $day.'T08:00:00+09:00'],
        ]]])]);

        $this->pull($user);

        $this->assertSame([$day, '07:30'], [$task->refresh()->due_on, $task->starts_at]);
        $prediction->refresh();
        $this->assertSame([$day, '07:30'], [$prediction->check_on, $prediction->check_at]);
        $this->assertSame(['前の晩に時間割を見れば、袋が玄関にあるはず', 70], [$prediction->statement, $prediction->confidence]);
    }
}
