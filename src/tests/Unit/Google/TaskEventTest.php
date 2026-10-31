<?php

namespace Tests\Unit\Google;

use App\Models\Prediction;
use App\Models\Task;
use App\Services\Google\TaskEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** タスクと Google の予定の形を行き来させる係（終日・時刻つき・確かめるタスク・済んだタスク・取り込みの日時） */
class TaskEventTest extends TestCase
{
    use RefreshDatabase;

    /** 開始時刻のないタスクは終日の予定（終わりは次の日）になり、題名・リンク・アプリの印がつくことを確かめる */
    public function test_an_all_day_task_becomes_a_date_event(): void
    {
        $task = Task::factory()->create(['title' => '牛乳を買う', 'due_on' => '2026-11-02']);

        $event = app(TaskEvent::class)->toEvent($task);

        $this->assertSame('牛乳を買う', $event['summary']);
        $this->assertSame(['date' => '2026-11-02'], $event['start']);
        $this->assertSame(['date' => '2026-11-03'], $event['end']);
        $this->assertSame('opaque', $event['transparency']);
        $this->assertSame((string) $task->id, $event['extendedProperties']['private']['suiri_task_id']);
        $this->assertStringStartsWith('http', $event['source']['url']);
        $this->assertStringContainsString($event['source']['url'], $event['description']);
    }

    /** 時刻つきで長さのないタスクは、既定の30分の予定（東京の時刻）になることを確かめる */
    public function test_a_timed_task_without_length_lasts_the_default_minutes(): void
    {
        $task = Task::factory()->create(['due_on' => '2026-11-02', 'starts_at' => '23:45', 'duration_minutes' => null]);

        $event = app(TaskEvent::class)->toEvent($task);

        $this->assertSame(['dateTime' => '2026-11-02T23:45:00', 'timeZone' => 'Asia/Tokyo'], $event['start']);
        $this->assertSame(['dateTime' => '2026-11-03T00:15:00', 'timeZone' => 'Asia/Tokyo'], $event['end']);
    }

    /** 長さのあるタスクは、その分だけの予定になることを確かめる */
    public function test_a_timed_task_uses_its_length(): void
    {
        $task = Task::factory()->at('09:00', 90)->create(['due_on' => '2026-11-02']);

        $this->assertSame('2026-11-02T10:30:00', app(TaskEvent::class)->toEvent($task)['end']['dateTime']);
    }

    /** 確かめるタスクは時間を塞がず、題名は決まった言葉（予想の文を送らない）、リンクは答え合わせの画面になることを確かめる */
    public function test_a_verify_task_is_transparent_and_hides_the_statement(): void
    {
        $prediction = Prediction::factory()->create(['statement' => '秘密の予想の文']);
        $task = Task::factory()->verify($prediction)->create();

        $event = app(TaskEvent::class)->toEvent($task);

        $this->assertSame('transparent', $event['transparency']);
        $this->assertSame(config('suiri.calendar.verify_summary'), $event['summary']);
        $this->assertStringNotContainsString('秘密の予想の文', json_encode($event, JSON_UNESCAPED_UNICODE));
    }

    /** 済んだタスクの予定は消さずに、題名の頭に「✓ 」が付くことを確かめる */
    public function test_a_done_task_keeps_its_event_with_a_check_mark(): void
    {
        $task = Task::factory()->done()->create(['title' => '週報を書く']);

        $this->assertSame('✓ 週報を書く', app(TaskEvent::class)->toEvent($task)['summary']);
    }

    /** 予定の日時から、タスクの期日・開始時刻（東京）・長さ、終日なら日付だけに戻せることを確かめる */
    public function test_times_from_an_event(): void
    {
        $taskEvent = app(TaskEvent::class);

        $this->assertSame(
            ['due_on' => '2026-11-05', 'starts_at' => '10:00', 'duration_minutes' => 45],
            $taskEvent->timesFrom(['start' => ['dateTime' => '2026-11-05T01:00:00Z'], 'end' => ['dateTime' => '2026-11-05T01:45:00Z']]),
        );
        $this->assertSame(
            ['due_on' => '2026-11-06', 'starts_at' => null, 'duration_minutes' => null],
            $taskEvent->timesFrom(['start' => ['date' => '2026-11-06'], 'end' => ['date' => '2026-11-07']]),
        );
        $this->assertNull($taskEvent->timesFrom(['start' => []]));
    }
}
