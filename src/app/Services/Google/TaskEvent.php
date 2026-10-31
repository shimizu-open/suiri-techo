<?php

namespace App\Services\Google;

use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

/**
 * タスクと Google の予定の形を行き来させる係（計算だけ。Google にも DB にも触らない）。
 * - toEvent()：タスクを予定の中身にする。送るのは題名・日時・アプリに戻るリンクと、どのタスクの予定かを見分ける番号（suiri_task_id）だけ
 *   （番号はアプリの中の通し番号で、記録の中身ではない。メモや事件の本文は入れない）
 * - timesFrom()：予定の日時を、タスクの期日・開始時刻・長さに戻す（取り込みで使う）
 */
class TaskEvent
{
    /**
     * タスクを Google の予定の中身（events.insert の本体）にする。
     * 確かめるタスクは時間を塞がない（transparent）・題名は決まった言葉・リンクは答え合わせの画面。済んだタスクは題名の頭に「✓ 」
     *
     * @return array<string, mixed>
     */
    public function toEvent(Task $task): array
    {
        $url = $this->link($task);

        return [
            'summary' => $this->summary($task),
            'description' => config('suiri.calendar.link_label').$url,
            ...$this->times($task),
            'transparency' => $task->isVerify() ? 'transparent' : 'opaque',
            'source' => ['title' => config('suiri.calendar.name'), 'url' => $url],
            'extendedProperties' => ['private' => ['suiri_task_id' => (string) $task->id]],
        ];
    }

    /**
     * タスクの期日と時刻を、予定の start・end にする。
     * 開始時刻がなければ終日（end は次の日。Google の決まりで終わりは含まない）、あれば長さ（なければ config の既定の分）だけの予定
     *
     * @return array{start: array<string, string>, end: array<string, string>}
     */
    public function times(Task $task): array
    {
        $timeZone = config('suiri.calendar.timezone');

        // 開始時刻のない（終日の）タスクは、日付だけの予定にする
        if (! $task->hasTime()) {
            $day = CarbonImmutable::parse($task->due_on, $timeZone);

            return ['start' => ['date' => $day->toDateString()], 'end' => ['date' => $day->addDay()->toDateString()]];
        }

        $start = CarbonImmutable::parse($task->due_on.' '.$task->starts_at, $timeZone);
        $end = $start->addMinutes($task->duration_minutes ?: config('suiri.calendar.default_minutes'));

        return [
            'start' => ['dateTime' => $start->format('Y-m-d\TH:i:s'), 'timeZone' => $timeZone],
            'end' => ['dateTime' => $end->format('Y-m-d\TH:i:s'), 'timeZone' => $timeZone],
        ];
    }

    /**
     * 予定の日時を、タスクの期日（Y-m-d）・開始時刻（HH:MM か null）・長さ（分。終日なら null）に戻す。日時が読めなければ null
     *
     * @param  array<string, mixed>  $event
     * @return array{due_on: string, starts_at: ?string, duration_minutes: ?int}|null
     */
    public function timesFrom(array $event): ?array
    {
        $timeZone = config('suiri.calendar.timezone');

        // 終日の予定なら、日付だけを返す
        if (isset($event['start']['date'])) {
            return ['due_on' => $event['start']['date'], 'starts_at' => null, 'duration_minutes' => null];
        }

        // 開始の日時がない予定（形がおかしい）は、取り込まない
        if (! isset($event['start']['dateTime'])) {
            return null;
        }

        $start = CarbonImmutable::parse($event['start']['dateTime'])->setTimezone($timeZone);
        $end = isset($event['end']['dateTime']) ? CarbonImmutable::parse($event['end']['dateTime']) : $start;
        $minutes = (int) max(1, min(65535, $start->diffInMinutes($end, true)));

        return ['due_on' => $start->toDateString(), 'starts_at' => $start->format('H:i'), 'duration_minutes' => $minutes];
    }

    /** 予定の題名を決める。確かめるタスクは決まった言葉（題名に予想の文が入るため送らない）。済んだタスクは頭に「✓ 」 */
    private function summary(Task $task): string
    {
        $title = $task->isVerify() ? config('suiri.calendar.verify_summary') : $task->title;

        return ($task->isDone() ? config('suiri.calendar.done_prefix') : '').$title;
    }

    /** 予定からアプリに戻るリンク。確かめるタスクは答え合わせの画面、ふつうのタスクは直す画面 */
    private function link(Task $task): string
    {
        // 確かめるタスクで、もとの予想が残っていれば、答え合わせの画面へ
        if ($task->answeredByPrediction() && Route::has('predictions.show')) {
            return route('predictions.show', $task->prediction_id);
        }

        // ふつうのタスクは、そのタスクを直す画面へ。画面がまだない（作りかけの）ときは、トップへ
        if (Route::has('tasks.edit')) {
            return route('tasks.edit', $task->id);
        }

        return url('/');
    }
}
