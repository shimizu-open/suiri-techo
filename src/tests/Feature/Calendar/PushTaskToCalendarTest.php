<?php

namespace Tests\Feature\Calendar;

use App\Contracts\CalendarApi;
use App\Jobs\PushTaskToCalendar;
use App\Jobs\RemoveTaskFromCalendar;
use App\Models\CaseFile;
use App\Models\Prediction;
use App\Models\Task;
use App\Services\Google\CalendarApi as GoogleCalendarApi;
use App\Services\Google\Exceptions\GoogleApiFailed;
use App\Services\Google\TaskEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** 書き出しの仕事（予定の ID を1度だけ決める・作る・置き換える・消す・Google が止まっても保存は成功・本文を送らない） */
class PushTaskToCalendarTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** 書き出しの仕事をその場で動かす（テストでは偽の Google カレンダーに書く） */
    private function push(Task $task): void
    {
        app()->call([new PushTaskToCalendar($task->id), 'handle']);
    }

    /** 初めての書き出しで、ハイフンを抜いた UUID の予定 ID を決めて保存し、同じ ID で何度書いても予定は1つのままなことを確かめる */
    public function test_the_event_id_is_chosen_once_and_reused(): void
    {
        Queue::fake();
        $user = $this->connectedUser();
        $task = Task::factory()->for($user)->create(['title' => '牛乳を買う']);

        $this->push($task);
        $eventId = $task->refresh()->calendar_event_id;
        $this->push($task);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $eventId);
        $this->assertSame($eventId, $task->refresh()->calendar_event_id);
        $events = $this->fakeApi()->events[$user->calendarConnection->app_calendar_id];
        $this->assertCount(1, $events);
        $this->assertSame('牛乳を買う', $events[$eventId]['summary']);
        $this->assertNotNull($task->calendar_synced_at);
        $this->assertNotNull($user->calendarConnection->refresh()->last_synced_at);
    }

    /** 済んだタスクの予定は残り、題名の頭に「✓ 」が付くことを確かめる（戻すと外れる） */
    public function test_a_completed_task_keeps_its_event_with_a_check_mark(): void
    {
        $user = $this->connectedUser();
        $task = Task::factory()->for($user)->create(['title' => '週報を書く']);
        $calendarId = $user->calendarConnection->app_calendar_id;

        $task->update(['done_at' => now()]);
        $this->assertSame('✓ 週報を書く', $this->fakeApi()->events[$calendarId][$task->calendar_event_id]['summary']);

        $task->update(['done_at' => null]);
        $this->assertSame('週報を書く', $this->fakeApi()->events[$calendarId][$task->calendar_event_id]['summary']);
    }

    /** 期日を消して「いつか」にしたタスクは、予定を消してつながりを外すことを確かめる */
    public function test_a_task_without_due_date_loses_its_event(): void
    {
        $user = $this->connectedUser();
        $task = Task::factory()->for($user)->create();
        $calendarId = $user->calendarConnection->app_calendar_id;
        $this->assertCount(1, $this->fakeApi()->events[$calendarId]);

        $task->update(['due_on' => null]);

        $this->assertCount(0, $this->fakeApi()->events[$calendarId]);
        $this->assertNull($task->refresh()->calendar_event_id);
    }

    /** タスクを消すと、その予定も専用カレンダーから消えることを確かめる */
    public function test_deleting_a_task_removes_the_event(): void
    {
        $user = $this->connectedUser();
        $task = Task::factory()->for($user)->create();
        $calendarId = $user->calendarConnection->app_calendar_id;

        $task->delete();

        $this->assertSame([], $this->fakeApi()->events[$calendarId]);
    }

    /** Google が止まっていても、タスクの保存は成功し（すぐ動く設定でも例外にならない）、仕事は失敗として試し直しに回ることを確かめる */
    public function test_a_google_outage_does_not_break_saving_the_task(): void
    {
        $user = $this->connectedUser();
        $this->fakeApi()->failing = true;

        $task = Task::factory()->for($user)->create(['title' => '止まっていても保存']);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => '止まっていても保存']);
        $task->update(['title' => '直しても保存']);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => '直しても保存']);

        $this->expectException(GoogleApiFailed::class);
        $this->push($task);
    }

    /** 他人の連携には書かず、つないでいない人の消す仕事は何もしないことを確かめる */
    public function test_jobs_only_touch_the_owners_calendar(): void
    {
        Queue::fake();
        $owner = $this->connectedUser();
        $other = $this->connectedUser();
        $task = Task::factory()->for($owner)->create();

        $this->push($task);
        app()->call([new RemoveTaskFromCalendar($task->user_id + 999, 'whatever'), 'handle']);

        $this->assertArrayNotHasKey($other->calendarConnection->app_calendar_id, $this->fakeApi()->events);
        $this->assertCount(1, $this->fakeApi()->events[$owner->calendarConnection->app_calendar_id]);
    }

    /** Google に送る中身は題名・日時・リンク・アプリの印だけで、メモや事件のなぞ・手がかり・見立て・予想の文が入らないことを確かめる */
    public function test_no_body_text_is_sent_to_google(): void
    {
        Queue::fake();
        Http::fake(['https://www.googleapis.com/*' => Http::response(['id' => 'ok'])]);
        $user = $this->connectedUser(['expires_at' => now()->addHour()]);
        $case = CaseFile::factory()->for($user)->create(['title' => '秘密のなぞ']);
        $case->clues()->create(['kind' => 'seen', 'body' => '秘密の手がかり']);
        $hypothesis = $case->hypotheses()->create(['body' => '秘密の見立て']);
        $prediction = Prediction::factory()->create(['hypothesis_id' => $hypothesis->id, 'statement' => '秘密の予想の文']);
        $verify = Task::factory()->verify($prediction)->create(['memo' => '秘密のメモ1', 'starts_at' => '18:00']);
        $normal = Task::factory()->for($user)->create(['title' => '牛乳を買う', 'memo' => '秘密のメモ2']);
        $this->app->instance(CalendarApi::class, app(GoogleCalendarApi::class));

        $this->push($verify);
        $this->push($normal);

        Http::assertSentCount(2);
        $allowed = ['id', 'summary', 'description', 'start', 'end', 'transparency', 'source', 'extendedProperties'];
        Http::assertSent(function (Request $request) use ($allowed) {
            $this->assertSame([], array_diff(array_keys($request->data()), $allowed));
            // 本文の言葉を1つずつ取り出して、送った JSON に入っていないかを見る
            foreach (['秘密のなぞ', '秘密の手がかり', '秘密の見立て', '秘密の予想の文', '秘密のメモ'] as $secret) {
                $this->assertStringNotContainsString($secret, $request->body());
            }

            return true;
        });
        Http::assertSent(fn (Request $request) => $request['summary'] === config('suiri.calendar.verify_summary') && $request['transparency'] === 'transparent');
        Http::assertSent(fn (Request $request) => $request['summary'] === '牛乳を買う' && $request['transparency'] === 'opaque');
    }

    /** 予定の中身を作る係が、カレンダーの決まった形（終日の end は次の日）で返すことを、仕事が使う形のまま確かめる */
    public function test_the_job_uses_the_task_event_shape(): void
    {
        Queue::fake();
        $user = $this->connectedUser();
        $task = Task::factory()->for($user)->create(['due_on' => '2026-11-02']);

        $this->push($task);

        $event = $this->fakeApi()->events[$user->calendarConnection->app_calendar_id][$task->refresh()->calendar_event_id];
        $this->assertSame(app(TaskEvent::class)->toEvent($task)['end'], $event['end']);
    }
}
