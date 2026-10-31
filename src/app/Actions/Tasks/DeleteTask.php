<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use LogicException;

/**
 * タスクを消す用事。モデルの delete() で消すので、カレンダーの担当の見張り役（オブザーバー）が予定も消せる。
 * 答え合わせ待ちの予想を確かめるタスクは消さない（受付の DeleteTaskRequest が先に断る。消すなら予想の取り下げ WithdrawPrediction を使う）
 */
class DeleteTask
{
    /** タスクを消す。答え合わせ待ちの予想を確かめるタスクなら、消さずに例外にする */
    public function handle(Task $task): void
    {
        // 答え合わせ待ちの予想を確かめるタスクなら止める（答え合わせ待ちの予想だけが残ってしまうため）
        if ($task->checksPendingPrediction()) {
            throw new LogicException('答え合わせ待ちの予想を確かめるタスクは、予想の取り下げで消します。');
        }

        $task->delete();
    }
}
