<?php

use App\Http\Controllers\Tasks\CompleteTaskController;
use App\Http\Controllers\Tasks\DraftTaskController;
use App\Http\Controllers\Tasks\ReopenTaskController;
use App\Http\Controllers\Tasks\TaskController;
use Illuminate\Support\Facades\Route;

/*
| タスク（機能：Tasks）。ログインして同意した人だけ。住所とルート名は docs/design/basic-design.md の 2-3。
| - 一覧（?view=today|upcoming|someday|done）・くわしく作る画面・作る・直す画面・直す・消す
| - すばやく足す欄の「くわしく」（POST /tasks/draft）：入れた題名を持って、くわしく作る画面へ移るだけ（まだ作らない）
| - 完了（POST /tasks/{task}/done。かかった時間も一緒に送れる）・未完了に戻す（DELETE /tasks/{task}/done）
| 他人のタスクは、Policy（持ち主だけ）で見る・直す・消すことができない
*/

Route::middleware(['auth', 'agreed'])->group(function () {
    Route::resource('tasks', TaskController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        // 同じ動作への指定はあとから書いたものが上書きするので、動作ごとに1回でまとめて書く
        ->middlewareFor('store', 'throttle:writes')
        ->middlewareFor('edit', 'can:update,task')
        ->middlewareFor('update', ['throttle:writes', 'can:update,task'])
        ->middlewareFor('destroy', ['throttle:writes', 'can:delete,task']);

    Route::post('/tasks/draft', DraftTaskController::class)->name('tasks.draft');
    Route::post('/tasks/{task}/done', CompleteTaskController::class)
        ->middleware('throttle:writes')->can('update', 'task')->name('tasks.done');
    Route::delete('/tasks/{task}/done', ReopenTaskController::class)
        ->middleware('throttle:writes')->can('update', 'task')->name('tasks.undone');
});
