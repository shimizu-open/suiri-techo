<?php

namespace App\Actions\Tasks;

use App\Models\Task;

/**
 * タスクを済みにする用事。かかった時間（分）が渡されれば一緒に記録する（見積もりの答え合わせ）。
 * すでに済んでいるタスクなら、済んだ日時は変えずに、かかった時間だけを足す（済んだあとに時間を入れるとき）
 */
class CompleteTask
{
    /** タスクを済みにして保存し、そのタスクを返す */
    public function handle(Task $task, ?int $actualMinutes = null): Task
    {
        $task->done_at ??= now();
        // かかった時間が入っていれば記録する（空なら前の値をそのまま残す）
        if ($actualMinutes !== null) {
            $task->actual_minutes = $actualMinutes;
        }
        $task->save();

        return $task;
    }
}
