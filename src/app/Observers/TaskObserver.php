<?php

namespace App\Observers;

use App\Enums\CalendarStatus;
use App\Jobs\PushTaskToCalendar;
use App\Jobs\RemoveTaskFromCalendar;
use App\Models\CalendarConnection;
use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * タスクを見張って、Google カレンダーへの書き出しの仕事を出す係（CalendarServiceProvider が Task::observe で結ぶ）。
 * タスクの担当は Google のことを知らなくてよい。作る・直す・済んだ・戻した → PushTaskToCalendar、消した → RemoveTaskFromCalendar。
 * 仕事は保存が確定してから積む（afterCommit）。カレンダーとつないでいない人のタスクでは何もしない。
 * 仕事を出すのに失敗しても、タスクの保存は成功のままにする（記録して終える）
 */
class TaskObserver
{
    /** 予定の中身に関わる列。これ以外（メモ・かかった時間など）だけが変わったときは書き出さない */
    private const SENT_COLUMNS = ['title', 'due_on', 'starts_at', 'duration_minutes', 'done_at', 'kind', 'prediction_id'];

    /**
     * 保存の直前に、予定の ID（ハイフンを抜いた UUID）を決めておく。期日があり、まだ ID がなく、つないでいる人のタスクだけ。
     * タスクと一緒に保存されるので、試し直しでも同じ ID を使い（二重登録しない）、消すときにも ID が分かる
     */
    public function saving(Task $task): void
    {
        // 期日がない・もう ID がある・つないでいない人なら、決めない
        if (blank($task->due_on) || filled($task->calendar_event_id) || ! $this->connected($task)) {
            return;
        }

        $task->calendar_event_id = str_replace('-', '', (string) Str::uuid());
    }

    /** タスクを作ったら、期日があるときだけ書き出す */
    public function created(Task $task): void
    {
        // 期日のない（いつかの）タスクは予定にしない
        if (blank($task->due_on)) {
            return;
        }

        $this->push($task);
    }

    /** タスクを直した・済んだ・戻したら、予定の中身に関わる列が変わったときだけ書き出す */
    public function updated(Task $task): void
    {
        // 予定に出る列が変わっていなければ、書き出さない
        if (! $task->wasChanged(self::SENT_COLUMNS)) {
            return;
        }

        $this->push($task);
    }

    /** タスクを消したら、書き出した予定があるときだけ消す仕事を出す */
    public function deleted(Task $task): void
    {
        // まだ予定にしていない・つないでいない人なら、何もしない
        if (blank($task->calendar_event_id) || ! $this->connected($task)) {
            return;
        }

        $this->safely($task, fn () => RemoveTaskFromCalendar::dispatch($task->user_id, $task->calendar_event_id)->afterCommit());
    }

    /** つないでいる人のタスクなら、書き出しの仕事を出す */
    private function push(Task $task): void
    {
        // カレンダーとつないでいない（切れた）人のタスクなら、何もしない
        if (! $this->connected($task)) {
            return;
        }

        $this->safely($task, fn () => PushTaskToCalendar::dispatch($task->id)->afterCommit());
    }

    /** そのタスクの持ち主が、いまカレンダーとつながっているかを返す */
    private function connected(Task $task): bool
    {
        return CalendarConnection::where('user_id', $task->user_id)->where('status', CalendarStatus::Active)->exists();
    }

    /** 仕事を出す。失敗しても（すぐ動く設定で Google が止まっていたときなど）タスクの保存は止めず、id とエラーの種類だけ記録する */
    private function safely(Task $task, callable $dispatch): void
    {
        // 仕事を出してみる。失敗したら記録だけして、タスクの保存はそのまま成功させる
        try {
            $dispatch();
        } catch (Throwable $e) {
            Log::warning('カレンダーへの書き出しの仕事を出せなかった', ['task_id' => $task->id, 'error' => class_basename($e)]);
        }
    }
}
