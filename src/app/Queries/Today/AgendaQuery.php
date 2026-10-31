<?php

namespace App\Queries\Today;

use App\Contracts\CalendarAgenda;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 今日の画面の「今日の予定とタスク」を組み立てる係。
 * Google カレンダーの今日の予定（CalendarAgenda。つないでいなければ空）と、今日が期日のタスクを1つの並びに混ぜ、
 * 終日のものを上に、あとは開始時刻の順に並べる（同じ時刻なら予定が先）。
 * 終わってから少しのあいだの予定には「さっき終わった予定」の印（just_ended）を付ける（提案 S-6：気になったことを記録しやすく）。
 * 1件の形：['type' => 'event'|'task', 'title', 'starts_at' => ?'HH:MM', 'ends_at' => ?'HH:MM', 'all_day' => bool, 'task' => ?Task, 'just_ended' => bool]
 */
class AgendaQuery
{
    /** 今日の予定の窓口を受け取る（テストでは偽物に差し替える） */
    public function __construct(private CalendarAgenda $calendar) {}

    /**
     * その日（'Y-m-d'）の予定とタスクを時刻順に返す。
     * $skipPredictionIds：上の「答え合わせ待ち」に出ている予想の id。その確かめるタスクは二重に出さない
     *
     * @param  list<int>  $skipPredictionIds
     * @return Collection<int, array<string, mixed>>
     */
    public function for(User $user, string $date, array $skipPredictionIds = []): Collection
    {
        $events = collect($this->calendar->eventsOn($user, $date))->map(fn (array $event) => [
            'type' => 'event',
            'title' => $event['title'],
            'starts_at' => $event['all_day'] ? null : $event['starts_at'],
            'ends_at' => $event['all_day'] ? null : ($event['ends_at'] ?? null),
            'all_day' => (bool) $event['all_day'] || blank($event['starts_at']),
            'task' => null,
        ])->map(fn (array $item) => $item + ['just_ended' => self::justEnded($date, $item)]);

        $tasks = $user->tasks()->where('due_on', $date)
            ->where(fn ($query) => $query->whereNull('prediction_id')->orWhereNotIn('prediction_id', $skipPredictionIds))
            ->orderBy('id')->get()
            ->map(fn (Task $task) => [
                'type' => 'task',
                'title' => $task->title,
                'starts_at' => $task->starts_at,
                'ends_at' => self::endOf($task),
                'all_day' => ! $task->hasTime(),
                'task' => $task,
                'just_ended' => false,
            ]);

        return $events->concat($tasks)
            ->sortBy(fn (array $item) => ($item['all_day'] ? '0' : '1').'|'.($item['starts_at'] ?? '').'|'.($item['type'] === 'event' ? '0' : '1'))
            ->values();
    }

    /** 予定が、終わった時刻を過ぎて決まった時間（config('suiri.suggest.just_ended_hours')）以内なら true を返す */
    private static function justEnded(string $date, array $item): bool
    {
        // 終日の予定・終わりの時刻がない予定は、いつ終わったかわからないので印を付けない
        if ($item['all_day'] || blank($item['ends_at'])) {
            return false;
        }
        $timeZone = config('suiri.calendar.timezone');
        $end = Carbon::parse("{$date} {$item['ends_at']}", $timeZone);
        $now = now($timeZone);

        return $now->gte($end) && $now->lt($end->copy()->addHours((int) config('suiri.suggest.just_ended_hours')));
    }

    /** タスクの終わりの時刻（開始時刻 ＋ 予想時間）を返す。どちらかがなければ null */
    private static function endOf(Task $task): ?string
    {
        // 開始時刻と予想時間の両方があるときだけ、終わりの時刻を出せる
        if (! $task->hasTime() || ! $task->duration_minutes) {
            return null;
        }

        return Carbon::createFromFormat('H:i', $task->starts_at)->addMinutes($task->duration_minutes)->format('H:i');
    }
}
