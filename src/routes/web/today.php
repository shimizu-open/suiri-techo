<?php

use App\Http\Controllers\Today\TodayController;
use Illuminate\Support\Facades\Route;

/*
| 今日の画面（機能：Today）。ログインして同意した人の最初の画面（App\Support\Landing）。
| 上から：記録のきっかけ → 答え合わせ待ち → 今日の予定とタスク → 今日の練習 → 捜査中の事件
*/

Route::middleware(['auth', 'agreed'])->group(function () {
    Route::get('/today', TodayController::class)->name('today');
});
