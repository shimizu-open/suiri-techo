<?php

use App\Http\Controllers\Calendar\ConnectCalendarController;
use App\Http\Controllers\Calendar\DisconnectCalendarController;
use Illuminate\Support\Facades\Route;

/*
| Google カレンダー連携（機能：Calendar）。ログインして同意した人だけ。回数制限は calendar（つなぐ・やめる）。
| つなぐ前の説明 → 同意して Google へ（POST）→ Google から戻る → 設定の画面。やめるのは DELETE。
| 住所に連携の id を入れない（いつもログイン中の本人の連携だけを扱う）
*/

Route::middleware(['auth', 'agreed', 'throttle:calendar'])->group(function () {
    Route::get('/calendar/connect', [ConnectCalendarController::class, 'show'])->name('calendar.connect');
    Route::post('/calendar/connect', [ConnectCalendarController::class, 'redirect'])->name('calendar.redirect');
    Route::get('/calendar/callback', [ConnectCalendarController::class, 'callback'])->name('calendar.callback');
    Route::delete('/calendar', DisconnectCalendarController::class)->name('calendar.disconnect');
});
