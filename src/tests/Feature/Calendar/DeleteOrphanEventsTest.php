<?php

namespace Tests\Feature\Calendar;

use App\Contracts\CalendarApi;
use App\Jobs\PullCalendarChanges;
use App\Jobs\PushAllTasksToCalendar;
use App\Jobs\PushTaskToCalendar;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Models\User;
use App\Services\Google\CalendarApi as GoogleCalendarApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 連携が切れている間に消したタスクの予定が、専用カレンダーに残らないこと（DeleteOrphanEvents）。
 * つなぎ直したとき（PushAllTasksToCalendar）と、全件を読み直したとき（ApplyCalendarChanges）に、
 * アプリの印（suiri_task_id）が本人の今あるタスクに当たらない予定だけを消す。Google の応答は Http::fake の偽物（本物の窓口を通す）
 */
class DeleteOrphanEventsTest extends TestCase
{
    use RefreshDatabase;

    private const CAL = 'app-cal@group.calendar.google.com';

    /** テストごとに、カレンダーの窓口を本物（REST を呼ぶ係）にし、キューは積むだけにする */
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(CalendarApi::class, $this->app->make(GoogleCalendarApi::class));
        Queue::fake();
    }

    /** つないだ利用者を作る（専用カレンダーの ID は決まった値） */
    private function connectedUser(array $attributes = []): User
    {
        return CalendarConnection::factory()->create(['app_calendar_id' => self::CAL, ...$attributes])->user;
    }

    /**
     * 専用カレンダーの予定の一覧（GET）にはこの予定を返し、予定を消す送信（DELETE）は成功にする偽の Google を用意する
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function fakeGoogle(array $items): void
    {
        Http::fake(fn (Request $request) => $request->method() === 'GET'
            ? Http::response(['items' => $items, 'nextSyncToken' => 'sync-new'])
            : Http::response('', 204));
    }

    /** アプリが書いた形の予定（印つき）を作る。番号を null にすると、印のない予定（利用者が手で足したもの） */
    private function event(string $id, int|string|null $taskId, string $status = 'confirmed'): array
    {
        $event = ['id' => $id, 'status' => $status, 'start' => ['date' => '2026-11-02'], 'end' => ['date' => '2026-11-03']];

        return $taskId === null ? $event : $event + ['extendedProperties' => ['private' => ['suiri_task_id' => (string) $taskId]]];
    }

    /** 予定を消す送信（DELETE）を受けた予定の ID を返す */
    private function deletedEventIds(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0])->filter(fn (Request $request) => $request->method() === 'DELETE')
            ->map(fn (Request $request) => basename(parse_url($request->url(), PHP_URL_PATH)))->values()->all();
    }

    /**
     * つなぎ直したときのまとめて書き出す仕事で、もうないタスク・他人のタスクの番号がついた予定だけを消し、
     * 今あるタスクの予定・印のない予定・消された印の予定には触らないことを確かめる
     */
    public function test_reconnecting_deletes_events_of_tasks_that_no_longer_exist(): void
    {
        $user = $this->connectedUser();
        $kept = Task::factory()->for($user)->create();
        $deleted = Task::factory()->for($user)->create();
        $deletedId = $deleted->id;
        $deleted->deleteQuietly();
        $others = Task::factory()->for(User::factory()->create())->create();
        $this->fakeGoogle([
            $this->event('ev-kept', $kept->id), $this->event('ev-gone', $deletedId), $this->event('ev-others', $others->id),
            $this->event('ev-by-hand', null), $this->event('ev-cancelled', $deletedId, 'cancelled'),
        ]);
        // タスクを作ったときに見張り役が積んだ仕事を数えないよう、キューを空にする
        Queue::fake();

        app()->call([new PushAllTasksToCalendar($user->id), 'handle']);

        $this->assertEqualsCanonicalizing(['ev-gone', 'ev-others'], $this->deletedEventIds());
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && $request->url() === config('suiri.calendar.urls.api').'/calendars/'.rawurlencode(self::CAL).'/events/ev-gone');
        Queue::assertPushed(PushTaskToCalendar::class, fn ($job) => $job->taskId === $kept->id);
    }

    /** つなぎ直したときは、前に予定を書いた済んだタスクも書き直しに出す（Google の予定を「✓」つきにするため）ことを確かめる */
    public function test_reconnecting_also_repushes_linked_done_tasks(): void
    {
        $user = $this->connectedUser();
        $done = Task::factory()->for($user)->done()->create();
        $done->forceFill(['calendar_event_id' => 'ev-done'])->saveQuietly();
        $this->fakeGoogle([$this->event('ev-done', $done->id)]);
        // タスクを作ったときに見張り役が積んだ仕事を数えないよう、キューを空にする
        Queue::fake();

        app()->call([new PushAllTasksToCalendar($user->id), 'handle']);

        Queue::assertPushed(PushTaskToCalendar::class, fn ($job) => $job->taskId === $done->id);
        $this->assertSame([], $this->deletedEventIds());
    }

    /** 全件の読み直し（印がない）でも、もうないタスクの予定を消すことを確かめる */
    public function test_a_full_sync_deletes_orphan_events(): void
    {
        $user = $this->connectedUser(['sync_token' => null]);
        $this->fakeGoogle([$this->event('ev-gone', 999999)]);

        app()->call([new PullCalendarChanges($user->calendarConnection->id), 'handle']);

        $this->assertSame(['ev-gone'], $this->deletedEventIds());
        $this->assertSame('sync-new', $user->calendarConnection->refresh()->sync_token);
    }

    /** 全件の読み直しで予定を消すのに Google が失敗しても（503）、取り込みは止めずに新しい印を保存することを確かめる（次の機会にまた片づける） */
    public function test_a_failed_sweep_does_not_block_the_full_sync(): void
    {
        $user = $this->connectedUser(['sync_token' => null]);
        Http::fake(fn (Request $request) => $request->method() === 'GET'
            ? Http::response(['items' => [$this->event('ev-gone', 999999)], 'nextSyncToken' => 'sync-new'])
            : Http::response(['error' => ['code' => 503]], 503));

        app()->call([new PullCalendarChanges($user->calendarConnection->id), 'handle']);

        $this->assertSame(['ev-gone'], $this->deletedEventIds());
        $this->assertSame('sync-new', $user->calendarConnection->refresh()->sync_token);
    }

    /** 差分の取り込み（印が生きている）では、届いた予定にもうないタスクの番号があっても消さないことを確かめる（全件のときだけ比べる） */
    public function test_an_incremental_sync_does_not_delete(): void
    {
        $user = $this->connectedUser(['sync_token' => 'sync-0']);
        $this->fakeGoogle([$this->event('ev-gone', 999999)]);

        app()->call([new PullCalendarChanges($user->calendarConnection->id), 'handle']);

        $this->assertSame([], $this->deletedEventIds());
    }

    /** 連携が切れている人の分は、まとめて書き出す仕事でも Google に何も問い合わせないことを確かめる */
    public function test_a_broken_connection_is_left_alone(): void
    {
        $user = $this->connectedUser(['status' => 'broken']);
        $this->fakeGoogle([$this->event('ev-gone', 999999)]);

        app()->call([new PushAllTasksToCalendar($user->id), 'handle']);

        Http::assertNothingSent();
    }
}
