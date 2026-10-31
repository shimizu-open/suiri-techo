<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * タスクを直す用事（題名・メモ・期日・開始時刻・予想時間・かかった時間）。種類と、もとの予想は変えない。
 * 期日を消して「いつか」にしたときは、開始時刻も消す（時刻だけ残っても使い道がない）。
 * 答え合わせ待ちの予想を確かめるタスクなら、期日・開始時刻を予想の確かめる日・時刻にも写す（タスクと予想を1つのトランザクションで保存）
 */
class UpdateTask
{
    /** 予想の確かめる日を写す用事を受け取る */
    public function __construct(private SyncPredictionCheck $syncPredictionCheck) {}

    /**
     * 確かめ済みの入力でタスクを直して保存し、直したタスクを返す
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $task->fill($data);
            // 期日がなくなったら、開始時刻も消す
            if ($task->due_on === null) {
                $task->starts_at = null;
            }
            $task->save();
            $this->syncPredictionCheck->handle($task);

            return $task;
        });
    }
}
