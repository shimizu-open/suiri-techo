<?php

namespace App\Actions\Tasks;

use App\Enums\TaskKind;
use App\Models\Task;
use App\Models\User;

/**
 * タスクを1つ作る用事。種類はいつも「ふつう」（確かめるタスクは事件の担当の MakePrediction が作る）。
 * 期日のあるタスクのカレンダーへの書き出しは、カレンダーの担当が Task の見張り役（オブザーバー）で行うので、ここでは知らなくてよい
 */
class CreateTask
{
    /**
     * 確かめ済みの入力（題名・メモ・期日・開始時刻・予想時間）でその人のタスクを作り、作ったタスクを返す
     *
     * @param  array{title: string, memo?: ?string, due_on?: ?string, starts_at?: ?string, duration_minutes?: ?int}  $data
     */
    public function handle(User $user, array $data): Task
    {
        return $user->tasks()->create([...$data, 'kind' => TaskKind::Normal]);
    }
}
