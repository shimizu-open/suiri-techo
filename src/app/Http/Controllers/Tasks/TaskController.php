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
 * タスクの受付（前の一覧の住所・くわしく作る画面・作る・直す画面・直す・消す）。
 * 形の確かめは FormRequest、持ち主かどうかはルートの can、用事は Actions/Tasks、枠の振り分けは Queries/Tasks に任せる。
 * 一覧はきょうの画面（/today）にまとめたので、保存したあとは、きょうの画面のそのタスクが見える枠を開いて戻す（?open=）
 */
class TaskController extends Controller
{
    /**
     * 前の一覧の住所（/tasks?view=…）を、きょうの画面へ移す。一覧の種類（upcoming・someday・done）は、
     * きょうの画面のその枠を開く ?open= にそろえる（today・知らない値・なしなら、何も開かずにきょうの画面へ）
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect($this->todayUrl(TaskListQuery::normalizeOpen($request->query('view'))));
    }

    /**
     * くわしく作る画面を返す。きょうの画面の1行で足す欄の「くわしく」から来たときは、そこで入れた題名・期日（一度きりの入力）を引き継ぎ、
     * 題名の似た過去のタスクの実績を出す。期日の初めの値は今日
     */
    public function create(Request $request, SimilarTasksQuery $similar): View
    {
        return view('tasks.create', [
            'task' => new Task(['due_on' => today()->toDateString()]),
            'similar' => $similar->for($request->user(), $request->old('title')),
        ]);
    }

    /** タスクを作り、きょうの画面のそのタスクが見える枠へ戻す（1行で足す欄からでも、くわしく作る画面からでも同じ） */
    public function store(StoreTaskRequest $request, CreateTask $createTask): RedirectResponse
    {
        $task = $createTask->handle($request->user(), $request->validated());

        return redirect($this->listUrl($task))->with('status', __(config('suiri.tasks.flash.created'), ['title' => $task->title]));
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

    /** タスクを直して、きょうの画面のそのタスクが見える枠へ戻す（答え合わせ待ちの予想を確かめるタスクなら、予想の確かめる日も一緒に変わる） */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTask $updateTask): RedirectResponse
    {
        $updateTask->handle($task, $request->validated());

        return redirect($this->listUrl($task))->with('status', __(config('suiri.tasks.flash.updated'), ['title' => $task->title]));
    }

    /** タスクを消して、きょうの画面のそのタスクがいた枠へ戻す（答え合わせ待ちの予想を確かめるタスクは DeleteTaskRequest が断る） */
    public function destroy(DeleteTaskRequest $request, Task $task, DeleteTask $deleteTask): RedirectResponse
    {
        $to = $this->listUrl($task);
        $deleteTask->handle($task);

        return redirect($to)->with('status', __(config('suiri.tasks.flash.deleted'), ['title' => $task->title]));
    }

    /** そのタスクが入る、きょうの画面の枠を開く URL を返す（今日までのものは上に出るので、枠は開かない） */
    private function listUrl(Task $task): string
    {
        return $this->todayUrl(TaskListQuery::openOf($task, today()->toDateString()));
    }

    /** きょうの画面の URL を返す（開く枠があれば ?open= を付ける） */
    private function todayUrl(?string $open): string
    {
        return route('today', array_filter(['open' => $open]));
    }
}
