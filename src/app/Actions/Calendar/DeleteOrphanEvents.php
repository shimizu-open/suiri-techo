<?php

namespace App\Actions\Calendar;

use App\Contracts\CalendarApi;
use App\Models\CalendarConnection;
use App\Models\Task;

/**
 * 専用カレンダーに残った「もうないタスクの予定」を消す用事。
 * 連携が切れている間（broken）にタスクを消すと、予定を消す仕事は何もせずに終わり、タスクの行も消えるので、
 * 予定だけが Google に残る。つなぎ直したとき（PushAllTasksToCalendar）と、全件を読み直したとき（ApplyCalendarChanges）に片づける。
 * - 消すのは、アプリの印（extendedProperties.private.suiri_task_id）があって、その番号が本人の今あるタスクに当たらない予定だけ
 * - 専用カレンダーはアプリだけが書く場所（スコープ calendar.app.created で作ったもの）なので、アプリの印つきの予定を消しても、
 *   利用者がほかのカレンダーに入れた予定には触れない。印のない予定（利用者が専用カレンダーに手で足したもの）は消さない
 */
class DeleteOrphanEvents
{
    /** Google カレンダーの窓口を受け取る */
    public function __construct(private CalendarApi $api) {}

    /**
     * もうないタスクの予定を消し、消した数を返す。$events を渡さなければ、専用カレンダーの予定を全件読む。
     * Google が止まっていれば GoogleApiFailed、許可が切れていれば GoogleAuthBroken がそのまま上がる（呼ぶ側が試し直す・あきらめる）
     *
     * @param  list<array<string, mixed>>|null  $events  全件を読んだ予定（ApplyCalendarChanges が読んだものを使い回す）
     */
    public function handle(CalendarConnection $connection, ?array $events = null): int
    {
        $events ??= $this->api->listChangedEvents($connection, $connection->app_calendar_id, null)['events'];

        // アプリの印があって、まだ消されていない予定だけを「予定の ID => タスクの番号」にする
        $marked = collect($events)
            ->reject(fn (array $event) => ($event['status'] ?? null) === 'cancelled' || ! isset($event['id']))
            ->mapWithKeys(fn (array $event) => [$event['id'] => $event['extendedProperties']['private']['suiri_task_id'] ?? null])
            ->filter(fn ($taskId) => filled($taskId));

        // 印のついた予定がなければ、調べるものはない
        if ($marked->isEmpty()) {
            return 0;
        }

        $existing = Task::where('user_id', $connection->user_id)
            ->whereIn('id', $marked->values()->unique()->all())
            ->pluck('id')->map(fn ($id) => (string) $id)->flip();
        $orphans = $marked->reject(fn ($taskId) => $existing->has((string) $taskId))->keys();

        // 本人の今あるタスクに当たらない予定を1つずつ消す（もう消えていれば窓口が何もしない）
        foreach ($orphans as $eventId) {
            $this->api->deleteEvent($connection, $connection->app_calendar_id, (string) $eventId);
        }

        return $orphans->count();
    }
}
