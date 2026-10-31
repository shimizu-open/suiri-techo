<?php

namespace App\Http\Controllers\Tasks;

use App\Actions\Tasks\ReopenTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\ReopenTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

/**
 * 済んだタスクを未完了に戻す受付（DELETE /tasks/{task}/done）。元の画面（きょう。開いていた枠も ?open= ごと）へ戻す
 */
class ReopenTaskController extends Controller
{
    /** タスクを未完了に戻して、元の画面へ戻す */
    public function __invoke(ReopenTaskRequest $request, Task $task, ReopenTask $reopenTask): RedirectResponse
    {
        $reopenTask->handle($task);

        return redirect()->back(fallback: route('today'))->with('status', __(config('suiri.tasks.flash.reopened'), ['title' => $task->title]));
    }
}
