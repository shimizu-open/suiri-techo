<?php

namespace App\Jobs;

use App\Actions\Calendar\DeleteOrphanEvents;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Services\Google\Exceptions\GoogleAuthBroken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * カレンダーとつないだ（つなぎ直した）直後に、その人のタスクをまとめて書き出す後回しの仕事（ConnectCalendar が出す）。
 * 1. まだ済んでいない期日つきのタスクと、前に予定を書いたタスク（済んだ・いつかにした分の書き直しのため）を、1件ずつ PushTaskToCalendar にする
 *    （1件が失敗しても、ほかは書き出せる。失敗した分だけ試し直す）
 * 2. 専用カレンダーに残った、もうないタスクの予定を消す（DeleteOrphanEvents。連携が切れている間に消したタスクの分）
 * Google が止まっていれば 2 が失敗して、config の回数まで間を空けて全体を試し直す（1 の書き出しは同じ予定の置き換えなので、出し直しても二重にならない）
 */
class PushAllTasksToCalendar implements ShouldQueue
{
    use Queueable;

    /** 書き出す人の id を覚えて作る */
    public function __construct(public int $userId) {}

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

    /** その人のタスクを1件ずつ書き出しの仕事にしてから、もうないタスクの予定を片づける */
    public function handle(DeleteOrphanEvents $deleteOrphans): void
    {
        $connection = CalendarConnection::where('user_id', $this->userId)->first();

        // つないでいない・切れた・専用カレンダーがないなら、何もしない（つなぎ直したときにまた出る）
        if ($connection === null || ! $connection->isActive() || blank($connection->app_calendar_id)) {
            return;
        }

        $tasks = Task::where('user_id', $this->userId)
            ->where(fn ($query) => $query->where(fn ($open) => $open->open()->whereNotNull('due_on'))->orWhereNotNull('calendar_event_id'))
            ->select('id')->lazyById();

        // タスクを1件ずつ取り出して、書き出しの仕事を出す（済んだタスクは「✓」つきに、いつかにしたタスクは予定を消す）
        foreach ($tasks as $task) {
            PushTaskToCalendar::dispatch($task->id);
        }

        // もうないタスクの予定を消す。許可が切れていたら（連携は broken になっている）試し直さずに終える
        try {
            $deleteOrphans->handle($connection);
        } catch (GoogleAuthBroken) {
            return;
        }
    }

    /** 決めた回数を試してもだめだったときの記録。書くのは持ち主の id とエラーの種類だけ */
    public function failed(Throwable $e): void
    {
        Log::warning('つなぎ直したときの予定の片づけをあきらめた', ['user_id' => $this->userId, 'error' => class_basename($e)]);
    }
}
