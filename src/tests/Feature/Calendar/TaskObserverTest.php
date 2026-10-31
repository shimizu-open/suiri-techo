<?php

namespace Tests\Feature\Calendar;

use App\Jobs\PushAllTasksToCalendar;
use App\Jobs\PushTaskToCalendar;
use App\Jobs\RemoveTaskFromCalendar;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** タスクを見張る係：作る・直す・済む・戻す・消すで、書き出しの仕事が出るか（キューは積むだけの偽物） */
class TaskObserverTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** テストごとに、キューを積むだけにする */
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /** つないでいる人が期日つきのタスクを作ると、書き出しの仕事が出ることを確かめる */
    public function test_creating_a_dated_task_pushes_it(): void
    {
        $task = Task::factory()->for($this->connectedUser())->create();

        Queue::assertPushed(PushTaskToCalendar::class, fn ($job) => $job->taskId === $task->id);
    }

    /** 期日のない（いつかの）タスクを作っても、書き出さないことを確かめる */
    public function test_a_someday_task_is_not_pushed(): void
    {
        Task::factory()->someday()->for($this->connectedUser())->create();

        Queue::assertNotPushed(PushTaskToCalendar::class);
    }

    /** つないでいない人・連携が切れた人のタスクでは、何もしないことを確かめる */
    public function test_nothing_happens_without_an_active_connection(): void
    {
        $task = Task::factory()->create();
        $brokenTask = Task::factory()->for($this->connectedUser(['status' => 'broken']))->create(['calendar_event_id' => 'e1']);
        $task->update(['title' => '直した']);
        $brokenTask->delete();

        Queue::assertNothingPushed();
    }

    /** 題名・期日・時刻・済んだ・戻した、を直すと書き出し、メモだけを直したときは書き出さないことを確かめる */
    public function test_only_changes_that_reach_the_calendar_push(): void
    {
        $task = Task::factory()->for($this->connectedUser())->create();
        $changes = [['title' => '新しい題名'], ['done_at' => now()], ['done_at' => null], ['due_on' => '2026-12-01', 'starts_at' => '10:00']];

        $this->freshQueue();
        $task->update(['memo' => 'カレンダーには出ないメモ', 'actual_minutes' => 20]);
        Queue::assertNotPushed(PushTaskToCalendar::class);

        // 予定に出る変更を1つずつ試し、そのたびに書き出しの仕事が出るかを見る
        foreach ($changes as $change) {
            $this->freshQueue();
            $task->update($change);
            Queue::assertPushed(PushTaskToCalendar::class, 1);
        }
    }

    /** 同じタスクの書き出しは、待っている間は1つにまとめられることを確かめる（何度直しても、動くときに最新の中身を書く） */
    public function test_pending_pushes_of_the_same_task_are_merged(): void
    {
        $task = Task::factory()->for($this->connectedUser())->create();

        $task->update(['title' => 'もう一度直す']);

        Queue::assertPushed(PushTaskToCalendar::class, 1);
    }

    /** 予定の ID は、つないでいる人の期日つきのタスクを保存するときに決まり（ハイフンを抜いた UUID）、つないでいない人には付かないことを確かめる */
    public function test_the_event_id_is_chosen_when_saving(): void
    {
        $synced = Task::factory()->for($this->connectedUser())->create();
        $plain = Task::factory()->create();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $synced->calendar_event_id);
        $this->assertNull($plain->calendar_event_id);
    }

    /** キューを空にし、待っている仕事のまとめ（同じタスクは1つ）も消す */
    private function freshQueue(): void
    {
        Cache::flush();
        Queue::fake();
    }

    /** 書き出した予定のあるタスクを消すと、持ち主と予定の ID を覚えた「消す仕事」が出ることを確かめる */
    public function test_deleting_a_synced_task_removes_its_event(): void
    {
        $user = $this->connectedUser();
        $task = Task::factory()->for($user)->create(['calendar_event_id' => 'abc123']);
        $unsynced = Task::factory()->for($user)->someday()->create();

        $task->delete();
        $unsynced->delete();

        Queue::assertPushed(RemoveTaskFromCalendar::class, 1);
        Queue::assertPushed(RemoveTaskFromCalendar::class, fn ($job) => $job->userId === $user->id && $job->eventId === 'abc123');
    }

    /**
     * まとめて書き出す仕事は、その人のまだ済んでいない期日つきのタスクと、予定の ID を持つタスク（済んだものは「✓」つきに書き直す）を
     * 1件ずつ出し、予定の ID のない済んだタスク・いつかのタスク・他人のタスクは出さないことを確かめる
     */
    public function test_push_all_dispatches_open_dated_and_linked_tasks_of_the_user(): void
    {
        $user = $this->connectedUser();
        $open = Task::factory()->for($user)->create();
        $linkedDone = Task::factory()->for($user)->done()->create();
        $unlinkedDone = Task::factory()->for($user)->done()->create();
        $unlinkedDone->forceFill(['calendar_event_id' => null])->saveQuietly();
        Task::factory()->for($user)->someday()->create();
        $other = Task::factory()->for($this->connectedUser())->create();
        $this->freshQueue();

        app()->call([new PushAllTasksToCalendar($user->id), 'handle']);

        Queue::assertPushed(PushTaskToCalendar::class, 2);
        Queue::assertPushed(PushTaskToCalendar::class, fn ($job) => $job->taskId === $open->id);
        Queue::assertPushed(PushTaskToCalendar::class, fn ($job) => $job->taskId === $linkedDone->id);
        Queue::assertNotPushed(PushTaskToCalendar::class, fn ($job) => $job->taskId === $other->id);
    }

    /** つないでいない人の分は、まとめて書き出す仕事でも何も出さないことを確かめる */
    public function test_push_all_does_nothing_without_a_connection(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create();
        CalendarConnection::factory()->broken()->create();
        $this->freshQueue();

        app()->call([new PushAllTasksToCalendar($user->id), 'handle']);

        Queue::assertNothingPushed();
    }
}
