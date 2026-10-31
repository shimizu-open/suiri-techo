<?php

namespace App\Http\Controllers\Cases;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 予定・タスクの行の「気になる」の受付（POST /cases/draft）。まだ作らずに、きっかけ（origin）と
 * 予定・タスクの題名の写し（label）を持って、事件を作る画面へ移る。
 * 題名を URL に載せない（アクセスの記録に本文を残さない）よう、POST で受けて一度きりの入力として渡す（タスクの tasks.draft と同じ形）
 */
class DraftCaseController extends Controller
{
    /** 入力を持って、事件を作る画面へ移る（長さや種類を確かめるのは、画面の組み立て NewCaseDraft と、作るときの StoreCaseRequest） */
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route('cases.create')->withInput($request->only(['origin', 'label']));
    }
}
