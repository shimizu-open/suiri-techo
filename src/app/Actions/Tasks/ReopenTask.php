<?php

namespace App\Actions\Tasks;

use App\Models\Task;

/**
 * 済んだタスクを未完了に戻す用事。かかった時間は残す（まちがえて戻したときに、入れ直さなくてよいように）
 */
class ReopenTask
{
    /** 済んだ印を外して保存し、そのタスクを返す */
    public function handle(Task $task): Task
    {
        $task->done_at = null;
        $task->save();

        return $task;
    }
}
