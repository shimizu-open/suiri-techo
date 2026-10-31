<?php

namespace App\Http\Controllers\Tasks;

use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\DeleteTask;
use App\Actions\Tasks\UpdateTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\DeleteTaskRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Task;
use App\Queries\Tasks\SimilarTasksQuery;
use App\Queries\Tasks\TaskListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * タスクの受付（一覧・くわしく作る画面・作る・直す画面・直す・消す）。
 * 形の確かめは FormRequest、持ち主かどうかはルートの can、用事は Actions/Tasks、一覧は Queries/Tasks に任せる。
 * 保存したあとは、そのタスクが見える一覧のタブへ戻す（今日の画面のすばやく足す欄からなら、今日の画面へ戻す）
 */
class TaskController extends Controller
{
    /** 一覧の画面を返す（?view=today|upcoming|someday|done。済みにした直後なら、さそう枠も出す） */
    public function index(Request $request, TaskListQuery $query): View
    {
        $user = $request->user();
        $view = TaskListQuery::normalize($request->query('view'));
        $today = today()->toDateString();

        return view('tasks.index', [
            'view' => $view,
            'today' => $today,
            'tasks' => $query->list($user, $view, $today),
            'counts' => $query->counts($user, $today),
            'justDone' => $query->justDone($user, session('task_done')),
        ]);
    }

    /**
     * くわしく作る画面を返す。すばやく足す欄の「くわしく」から来たときは、そこで入れた題名・期日（一度きりの入力）を引き継ぎ、
     * 題名の似た過去のタスクの実績を出す。期日の初めの値は今日
     */
    public function create(Request $request, SimilarTasksQuery $similar): View
    {
        return view('tasks.create', [
            'task' => new Task(['due_on' => today()->toDateString()]),
            'similar' => $similar->for($request->user(), $request->old('title')),
        ]);
    }

    /** タスクを作り、そのタスクが見える一覧（今日の画面から足したなら今日の画面）へ戻す */
    public function store(StoreTaskRequest $request, CreateTask $createTask): RedirectResponse
    {
        $task = $createTask->handle($request->user(), $request->validated());
        $to = $request->input('back') === 'today' ? route('today') : $this->listUrl($task);

        return redirect($to)->with('status', __(config('suiri.tasks.flash.created'), ['title' => $task->title]));
    }

    /** 直す画面を返す（題名の似た、ほかの済んだタスクの実績も出す） */
    public function edit(Request $request, Task $task, SimilarTasksQuery $similar): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'similar' => $similar->for($request->user(), $task->title, $task->id),
            'backUrl' => $this->listUrl($task),
        ]);
    }

    /** タスクを直して、そのタスクが見える一覧へ戻す（答え合わせ待ちの予想を確かめるタスクなら、予想の確かめる日も一緒に変わる） */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTask $updateTask): RedirectResponse
    {
        $updateTask->handle($task, $request->validated());

        return redirect($this->listUrl($task))->with('status', __(config('suiri.tasks.flash.updated'), ['title' => $task->title]));
    }

    /** タスクを消して、そのタスクがいた一覧へ戻す（答え合わせ待ちの予想を確かめるタスクは DeleteTaskRequest が断る） */
    public function destroy(DeleteTaskRequest $request, Task $task, DeleteTask $deleteTask): RedirectResponse
    {
        $to = $this->listUrl($task);
        $deleteTask->handle($task);

        return redirect($to)->with('status', __(config('suiri.tasks.flash.deleted'), ['title' => $task->title]));
    }

    /** そのタスクが入る一覧（タブ）の URL を返す */
    private function listUrl(Task $task): string
    {
        return route('tasks.index', ['view' => TaskListQuery::viewOf($task, today()->toDateString())]);
    }
}
