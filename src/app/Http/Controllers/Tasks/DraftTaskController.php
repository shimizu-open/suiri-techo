<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * きょうの画面の1行で足す欄の「くわしく」の受付（POST /tasks/draft）。まだ作らずに、入れた題名・期日を持って、
 * くわしく作る画面へ移る。題名を URL に載せない（サーバーの記録に本文を残さない）よう、POST で受けて一度きりの入力として渡す
 */
class DraftTaskController extends Controller
{
    /** 入力を持って、くわしく作る画面へ移る（確かめるのは、作るときの StoreTaskRequest） */
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route('tasks.create')->withInput($request->only(['title', 'due_on']));
    }
}
