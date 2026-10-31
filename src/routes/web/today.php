<?php

use App\Http\Controllers\Today\TodayController;
use Illuminate\Support\Facades\Route;

/*
| きょうの画面（機能：Today）。ログインして同意した人の最初の画面（App\Support\Landing）。前のタスクの一覧もここにまとめた。
| 上から：いまやることのカード → きょうの予定とやること（期日を過ぎたものも）→ これから・いつか・済み のたたんだ枠（?open= で開く）
| → 今日の練習 → 捜査中の事件
*/

Route::middleware(['auth', 'agreed'])->group(function () {
    Route::get('/today', TodayController::class)->name('today');
});
