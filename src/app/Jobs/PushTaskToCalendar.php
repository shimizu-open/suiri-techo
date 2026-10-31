<?php

namespace App\Jobs;

use App\Contracts\CalendarApi;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Services\Google\Exceptions\GoogleAuthBroken;
use App\Services\Google\TaskEvent;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * タスク1件を、Google カレンダーの専用カレンダーに書き出す後回しの仕事（worker の箱が片づける）。
 * タスクを作った・直した・済んだ・戻したときに TaskObserver が出す。動くときのタスクの中身を読むので、何度出しても最新になる。
 * - 期日があれば、予定を作るか置き換える（予定の ID はタスクごとに1度だけ決めて tasks.calendar_event_id に保存し、二重登録を防ぐ）
 * - 期日がなくなった（いつか）なら、予定を消してつながりを外す
 * Google が止まっていれば失敗して、config の回数まで間を空けて試し直す。許可が切れていたらあきらめる（つなぎ直したら全部書き出す）
 */
class PushTaskToCalendar implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    /** 書き出すタスクの id を覚えて作る（タスクそのものではなく id だけを積む） */
    public function __construct(public int $taskId) {}

    /** 同じタスクの仕事は、待っている間は1つにまとめる */
    public function uniqueId(): string
    {
        return (string) $this->taskId;
    }

    /** 試す回数（config） */
    public function tries(): int
    {
        return config('suiri.calendar.tries');
    }

    /**
     * 失敗したとき次に試すまで待つ秒数（config）
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return config('suiri.calendar.backoff');
    }

    /** タスクを読み直して、専用カレンダーの予定を作る・置き換える・消す */
    public function handle(CalendarApi $api, TaskEvent $taskEvent): void
    {
        $task = Task::find($this->taskId);
        $connection = $task ? CalendarConnection::where('user_id', $task->user_id)->first() : null;

        // タスクがもう消えた・持ち主がつないでいない・切れた・専用カレンダーがないなら、何もしない
        if ($task === null || $connection === null || ! $connection->isActive() || blank($connection->app_calendar_id)) {
            return;
        }

        // Google に書く。許可が切れていたら（連携は broken になっている）試し直さずに終える
        try {
            $this->push($api, $taskEvent, $task, $connection);
        } catch (GoogleAuthBroken) {
            return;
        }
    }

    /** 期日があれば予定を作る・置き換える。期日がなくなっていれば予定を消す */
    private function push(CalendarApi $api, TaskEvent $taskEvent, Task $task, CalendarConnection $connection): void
    {
        // 期日のない（いつかの）タスクは予定にしない。前に書いた予定があれば消して、つながりを外す
        if (blank($task->due_on)) {
            $this->unlink($api, $task, $connection);

            return;
        }

        // 予定の ID がまだなければ、ハイフンを抜いた UUID を決めて先に保存する（試し直しでも同じ ID を使い、二重にならない）
        if (blank($task->calendar_event_id)) {
            $task->calendar_event_id = str_replace('-', '', (string) Str::uuid());
            $task->saveQuietly();
        }

        $api->upsertEvent($connection, $connection->app_calendar_id, $task->calendar_event_id, $taskEvent->toEvent($task));
        $task->forceFill(['calendar_synced_at' => now()])->saveQuietly();
        $connection->forceFill(['last_synced_at' => now()])->save();
    }

    /** 前に書いた予定を消し、タスクとのつながりを外す（タスクを見張る係には知らせない） */
    private function unlink(CalendarApi $api, Task $task, CalendarConnection $connection): void
    {
        // 書いた予定がなければ、消すものはない
        if (blank($task->calendar_event_id)) {
            return;
        }

        $api->deleteEvent($connection, $connection->app_calendar_id, $task->calendar_event_id);
        $task->forceFill(['calendar_event_id' => null, 'calendar_synced_at' => null])->saveQuietly();
    }

    /** 決めた回数を試してもだめだったときの記録。書くのはタスクの id とエラーの種類だけ（題名は書かない） */
    public function failed(Throwable $e): void
    {
        Log::warning('カレンダーへの書き出しをあきらめた', ['task_id' => $this->taskId, 'error' => class_basename($e)]);
    }
}
