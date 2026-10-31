<?php

namespace Tests\Feature\Calendar;

use App\Jobs\PullCalendarChanges;
use App\Jobs\PushTaskToCalendar;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** 取り込み（専用カレンダーで動かした日時をタスクへ・消された予定はつながりだけ外す・新しい予定は取り込まない・410 で全件） */
class PullCalendarChangesTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** つないだ利用者と、予定とつながったタスクを用意して、[利用者, タスク] を返す */
    private function linkedTask(array $task = []): array
    {
        $user = $this->connectedUser(['sync_token' => 'sync-0']);
        $task = Task::factory()->for($user)->create(['calendar_event_id' => 'ev1', 'due_on' => '2026-11-02', ...$task]);

        return [$user, $task];
    }

    /** Google から返る予定（アプリの印つき）を作る */
    private function event(Task $task, array $fields): array
    {
        return ['id' => $task->calendar_event_id, 'status' => 'confirmed',
            'extendedProperties' => ['private' => ['suiri_task_id' => (string) $task->id]], ...$fields];
    }

    /** その連携の取り込みをその場で動かす */
    private function pull(User $user): void
    {
        app()->call([new PullCalendarChanges($user->calendarConnection->id), 'handle']);
    }

    /** 専用カレンダーで時刻つきに動かされた予定は、タスクの期日・開始時刻・長さに写り、Google には書き戻さないことを確かめる */
    public function test_a_moved_event_moves_the_task(): void
    {
        [$user, $task] = $this->linkedTask();
        $this->fakeApi()->changes = [$this->event($task, [
            'start' => ['dateTime' => '2026-11-04T19:00:00+09:00'], 'end' => ['dateTime' => '2026-11-04T20:15:00+09:00'],
        ])];
        Queue::fake();

        $this->pull($user);

        $task->refresh();
        $this->assertSame('2026-11-04', $task->due_on);
        $this->assertSame('19:00', $task->starts_at);
        $this->assertSame(75, $task->duration_minutes);
        Queue::assertNotPushed(PushTaskToCalendar::class);
    }

    /** 終日に動かされた予定は、期日だけが変わり、開始時刻が外れることを確かめる */
    public function test_an_event_moved_to_all_day_clears_the_time(): void
    {
        [$user, $task] = $this->linkedTask(['starts_at' => '09:00', 'duration_minutes' => 30]);
        $this->fakeApi()->changes = [$this->event($task, ['start' => ['date' => '2026-11-03'], 'end' => ['date' => '2026-11-04']])];

        $this->pull($user);

        $task->refresh();
        $this->assertSame('2026-11-03', $task->due_on);
        $this->assertNull($task->starts_at);
        $this->assertSame(30, $task->duration_minutes);
    }

    /** アプリが書いたままの予定（既定の30分）なら、タスクは何も変わらないことを確かめる */
    public function test_an_unchanged_event_keeps_the_task_as_it_is(): void
    {
        [$user, $task] = $this->linkedTask(['starts_at' => '08:00', 'duration_minutes' => null]);
        $this->fakeApi()->changes = [$this->event($task, [
            'start' => ['dateTime' => '2026-11-02T08:00:00+09:00'], 'end' => ['dateTime' => '2026-11-02T08:30:00+09:00'],
        ])];

        $this->pull($user);

        $this->assertNull($task->refresh()->duration_minutes);
        $this->assertSame('08:00', $task->starts_at);
    }

    /** 専用カレンダーで消された予定は、タスクを消さずに、つながりだけを外すことを確かめる */
    public function test_a_deleted_event_only_unlinks_the_task(): void
    {
        [$user, $task] = $this->linkedTask();
        $this->fakeApi()->changes = [$this->event($task, ['status' => 'cancelled'])];

        $this->pull($user);

        $this->assertModelExists($task);
        $this->assertNull($task->refresh()->calendar_event_id);
    }

    /** Google 側で新しく作られた予定（アプリの印なし）は取り込まず、タスクは増えないことを確かめる */
    public function test_events_created_in_google_are_ignored(): void
    {
        [$user] = $this->linkedTask();
        $this->fakeApi()->changes = [['id' => 'new1', 'status' => 'confirmed', 'summary' => 'Google で足した', 'start' => ['date' => '2026-11-09']]];

        $this->pull($user);

        $this->assertSame(1, Task::count());
    }

    /** ほかの人のタスクの印がついた予定が来ても、その人のタスクは動かさないことを確かめる */
    public function test_another_users_task_is_never_touched(): void
    {
        [$user] = $this->linkedTask();
        $others = Task::factory()->for(User::factory())->create(['calendar_event_id' => 'ev1', 'due_on' => '2026-11-02']);
        $this->fakeApi()->changes = [$this->event($others, ['start' => ['date' => '2026-12-25'], 'end' => ['date' => '2026-12-26'], 'status' => 'cancelled'])];

        $this->pull($user);

        $others->refresh();
        $this->assertSame('2026-11-02', $others->due_on);
        $this->assertSame('ev1', $others->calendar_event_id);
    }

    /** 前回の印を使って差分を読み、次回の印と取り込んだ日時を保存することを確かめる */
    public function test_the_sync_token_is_used_and_saved(): void
    {
        [$user] = $this->linkedTask();

        $this->pull($user);

        $connection = $user->calendarConnection->refresh();
        $this->assertSame(['sync-0'], $this->fakeApi()->syncTokensSeen);
        $this->assertSame('fake-sync-1', $connection->sync_token);
        $this->assertNotNull($connection->last_synced_at);
    }

    /** 印が古くなった（410）ら、印なしで全件を読み直して、新しい印を保存することを確かめる */
    public function test_an_expired_sync_token_triggers_a_full_sync(): void
    {
        [$user] = $this->linkedTask();
        $this->fakeApi()->expiredSyncToken = true;

        $this->pull($user);

        $this->assertSame(['sync-0', null], $this->fakeApi()->syncTokensSeen);
        $this->assertSame('fake-sync-2', $user->calendarConnection->refresh()->sync_token);
    }

    /** 指定なしの取り込みは、つながっていて専用カレンダーのある人ごとに1つずつ仕事を出すことを確かめる（切れた人は出さない） */
    public function test_the_scheduled_pull_fans_out_per_active_connection(): void
    {
        $active = $this->connectedUser()->calendarConnection;
        CalendarConnection::factory()->broken()->create();
        CalendarConnection::factory()->create(['app_calendar_id' => null]);
        Queue::fake();

        app()->call([new PullCalendarChanges, 'handle']);

        Queue::assertPushed(PullCalendarChanges::class, 1);
        Queue::assertPushed(PullCalendarChanges::class, fn ($job) => $job->connectionId === $active->id);
    }

    /** 切れた連携は取り込まず、Google にも聞かないことを確かめる */
    public function test_a_broken_connection_is_skipped(): void
    {
        $user = $this->connectedUser(['status' => 'broken']);

        $this->pull($user);

        $this->assertSame(0, $this->fakeApi()->calls);
    }

    /** 取り込みが15分ごとの予定に入っていることを確かめる */
    public function test_the_pull_is_scheduled_every_15_minutes(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains((string) $event->description, 'PullCalendarChanges'));

        $this->assertCount(1, $events);
        $this->assertSame('*/15 * * * *', $events->first()->expression);
    }
}
