<?php

namespace App\Http\Controllers\Tasks;

use App\Actions\Tasks\CompleteTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\CompleteTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

/**
 * タスクを済みにする受付（POST /tasks/{task}/done）。かかった時間（任意）も一緒に受け取れる。
 * 元の画面（今日・一覧）へ戻し、「かかった時間」と「気になることを記録」をさそう枠を出すために、済ませたタスクの id を知らせる
 */
class CompleteTaskController extends Controller
{
    /** タスクを済みにして、元の画面へ戻す */
    public function __invoke(CompleteTaskRequest $request, Task $task, CompleteTask $completeTask): RedirectResponse
    {
        $completeTask->handle($task, $request->validated('actual_minutes'));

        return redirect()->back(fallback: route('tasks.index'))->with('task_done', $task->id);
    }
}
