<?php

namespace App\Actions\Tasks;

use App\Models\Task;

/**
 * 確かめるタスクの期日・開始時刻を、もとの予想の確かめる日・時刻（check_on・check_at）に写す用事。
 * タスクを直したとき（UpdateTask）と、専用カレンダーで予定を動かされたとき（ApplyCalendarChanges）に呼ぶ。
 * 写すのは答え合わせ待ちの予想だけ。予想の文と確かさには触らない（保存後に書き換えない決まり）。呼ぶ側のトランザクションの中で使う
 */
class SyncPredictionCheck
{
    /** 答え合わせ待ちの予想の確かめるタスクで、期日・時刻が予想と違えば、予想の確かめる日・時刻を合わせる。直したら true */
    public function handle(Task $task): bool
    {
        // 答え合わせ待ちの予想を確かめるタスクでない・期日がないなら、写さない
        if (! $task->checksPendingPrediction() || blank($task->due_on)) {
            return false;
        }

        $prediction = $task->prediction;
        $prediction->fill(['check_on' => $task->due_on, 'check_at' => $task->starts_at]);

        // 予想の確かめる日・時刻がもう同じなら、保存しない
        if (! $prediction->isDirty(['check_on', 'check_at'])) {
            return false;
        }

        $prediction->save();

        return true;
    }
}
