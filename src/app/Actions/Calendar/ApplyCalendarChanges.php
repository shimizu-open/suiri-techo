<?php

namespace App\Actions\Calendar;

use App\Actions\Tasks\SyncPredictionCheck;
use App\Contracts\CalendarApi;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Services\Google\Exceptions\GoogleApiFailed;
use App\Services\Google\Exceptions\SyncTokenExpired;
use App\Services\Google\TaskEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 1人分の専用カレンダーの変化を、タスクに取り込む用事（PullCalendarChanges が呼ぶ）。
 * - 前回の印（sync_token）から変わった予定だけを読む。印がない・古い（410）なら全件を読み直す（ページは窓口が最後までたどって1つにする）
 * - 消された予定（cancelled）は先に見る。予定の ID だけで届くこともあるので、その人のタスクを予定の ID で探して、
 *   タスクとのつながり（calendar_event_id）だけを外す。タスクは消さない
 * - アプリが作った予定（suiri_task_id つき）で日時が動いていたら、タスクの期日・開始時刻・長さを合わせる
 *   （答え合わせ待ちの予想を確かめるタスクなら、予想の確かめる日・時刻も一緒に合わせる）
 * - 全件を読んだときは、Google の一覧にもうない予定とのつながりも外す（印が切れて、消された知らせを受け取れなかった分）。
 *   あわせて、もうないタスクの予定（連携が切れている間に消したタスクの分）を専用カレンダーから消す（DeleteOrphanEvents）
 * - Google 側で新しく作られた予定（suiri_task_id なし）は取り込まない
 * タスクは「見張る係に知らせない保存」で直す（取り込んだ変更を、また Google に書き出さないため）
 */
class ApplyCalendarChanges
{
    /** つながりを外すとき、一度に問い合わせる予定の ID の数 */
    private const UNLINK_CHUNK = 500;

    /** Google カレンダーの窓口と、予定とタスクの形を行き来させる係と、予想の確かめる日を写す用事と、残った予定を消す用事を受け取る */
    public function __construct(
        private CalendarApi $api,
        private TaskEvent $taskEvent,
        private SyncPredictionCheck $syncPredictionCheck,
        private DeleteOrphanEvents $deleteOrphans,
    ) {}

    /** 変わった予定を読んでタスクに写し、次回の印と取り込んだ日時を保存する。直したタスクの数を返す */
    public function handle(CalendarConnection $connection): int
    {
        $startedAt = now();
        $fullSync = blank($connection->sync_token);

        // 前回の印から変わった予定を読む（印がなければ全件）。印が古くなっていたら、印を捨てて全件を読み直す
        try {
            $page = $this->api->listChangedEvents($connection, $connection->app_calendar_id, $connection->sync_token);
        } catch (SyncTokenExpired) {
            $fullSync = true;
            $page = $this->api->listChangedEvents($connection, $connection->app_calendar_id, null);
        }

        $changed = 0;

        // 予定を1つずつ取り出して、消された予定ならつながりを外し、アプリのタスクの予定ならタスクに写す
        foreach ($page['events'] as $event) {
            $changed += $this->apply($connection, $event) ? 1 : 0;
        }

        // 全件を読んだときは、Google の一覧にない予定とのつながりを外し、もうないタスクの予定を消してから、新しい印を保存する
        if ($fullSync) {
            $changed += $this->unlinkMissing($connection, $page['events'], $startedAt);
            $this->sweepOrphans($connection, $page['events']);
        }

        $connection->forceFill(['sync_token' => $page['sync_token'], 'last_synced_at' => now()])->save();

        return $changed;
    }

    /**
     * 予定1つをタスクに写す。直したら true
     *
     * @param  array<string, mixed>  $event
     */
    private function apply(CalendarConnection $connection, array $event): bool
    {
        // ID のない予定は、どのタスクとも結びつけられないので何もしない
        if (! isset($event['id'])) {
            return false;
        }

        // 消された予定なら、アプリの印がなくても（ID だけの知らせでも）、その予定とつながっているタスクのつながりを外す
        if (($event['status'] ?? null) === 'cancelled') {
            return $this->unlink($connection, [$event['id']]) > 0;
        }

        $taskId = $event['extendedProperties']['private']['suiri_task_id'] ?? null;

        // アプリが作った印のない予定（Google 側で新しく作られたもの）は取り込まない
        if ($taskId === null) {
            return false;
        }

        // その人のタスクで、同じ予定とつながっているものだけを探す（他人のタスクや、つながりを外したタスクは触らない）
        $task = Task::where('user_id', $connection->user_id)->whereKey($taskId)->where('calendar_event_id', $event['id'])->first();

        // 見つからなければ何もしない
        if ($task === null) {
            return false;
        }

        return $this->moveTask($task, $event);
    }

    /**
     * 予定の日時がタスクと違っていたら、タスクの期日・開始時刻・長さを合わせる。直したら true。
     * 答え合わせ待ちの予想を確かめるタスクなら、予想の確かめる日・時刻も同じトランザクションで合わせる
     *
     * @param  array<string, mixed>  $event
     */
    private function moveTask(Task $task, array $event): bool
    {
        $times = $this->taskEvent->timesFrom($event);

        // 日時が読めない予定なら、何もしない
        if ($times === null) {
            return false;
        }

        // 終日の予定なら長さは今のまま。長さのない時刻つきタスクで、予定が既定の長さのままなら、長さは入れない
        if ($times['starts_at'] === null || ($task->duration_minutes === null && $times['duration_minutes'] === config('suiri.calendar.default_minutes'))) {
            $times['duration_minutes'] = $task->duration_minutes;
        }

        $task->fill($times);

        // 何も変わっていなければ（アプリが書いたままなら）保存しない
        if (! $task->isDirty(['due_on', 'starts_at', 'duration_minutes'])) {
            return false;
        }

        DB::transaction(function () use ($task) {
            $task->saveQuietly();
            $this->syncPredictionCheck->handle($task);
        });

        return true;
    }

    /**
     * 全件を読んだときに、Google の一覧にない予定とつながっているタスクのつながりを外す。外した数を返す。
     * 比べるのは、読み始める前に書き出しが済んでいたタスク（calendar_synced_at が読み始めより前）だけ。
     * まだ書き出していない・読んでいる間に書き出したタスクは、一覧になくても当たり前なので触らない（書き出しの仕事が予定を作る）
     *
     * @param  list<array<string, mixed>>  $events
     */
    private function unlinkMissing(CalendarConnection $connection, array $events, Carbon $startedAt): int
    {
        // Google にある予定の ID（消された印のものは除く）を、引きやすい形にする
        $present = collect($events)->reject(fn (array $event) => ($event['status'] ?? null) === 'cancelled')->pluck('id')->filter()->flip();

        $missing = Task::where('user_id', $connection->user_id)->whereNotNull('calendar_event_id')
            ->where('calendar_synced_at', '<', $startedAt)
            ->pluck('calendar_event_id')
            ->reject(fn (string $eventId) => $present->has($eventId));

        return $missing->chunk(self::UNLINK_CHUNK)->sum(fn ($ids) => $this->unlink($connection, $ids->values()->all()));
    }

    /**
     * 全件を読んだ一覧で、もうないタスクの予定を専用カレンダーから消す（DeleteOrphanEvents）。
     * Google の不調で消せなくても、取り込み（日時の写し・新しい印の保存）は止めない。消せなかった分は、つなぎ直したとき・次に全件を読んだときに片づく
     *
     * @param  list<array<string, mixed>>  $events
     */
    private function sweepOrphans(CalendarConnection $connection, array $events): void
    {
        // 残った予定を消してみる。Google の不調で失敗したら、連携の id とエラーの種類だけを記録して先へ進む
        try {
            $this->deleteOrphans->handle($connection, $events);
        } catch (GoogleApiFailed $e) {
            Log::warning('残った予定の片づけに失敗した', ['connection_id' => $connection->id, 'error' => class_basename($e)]);
        }
    }

    /**
     * その人のタスクで、渡した予定とつながっているもののつながりを外す（タスクは消さない）。外した数を返す。
     * 一括の書き換えなので、タスクを見張る係には知らせない（Google に書き戻さない）
     *
     * @param  list<string>  $eventIds
     */
    private function unlink(CalendarConnection $connection, array $eventIds): int
    {
        return Task::where('user_id', $connection->user_id)->whereIn('calendar_event_id', $eventIds)
            ->update(['calendar_event_id' => null, 'calendar_synced_at' => null]);
    }
}
