<?php

use App\Http\Controllers\Attic\AtticNoteController;
use App\Http\Controllers\Review\NorburyController;
use App\Http\Controllers\Review\ReviewController;
use Illuminate\Support\Facades\Route;

/*
| ふりかえり・ノーベリ帳・屋根裏の棚（機能：Review）。ふりかえり・退会の担当がここに書く。
| ログインが要る画面は Route::middleware(['auth', 'agreed'])->group(...) の中に置く。
| 住所とルート名は docs/design/basic-design.md の 2章のとおりにする
*/

Route::middleware(['auth', 'agreed'])->group(function () {
    // 週のふりかえり。{week} は週の月曜日の 'Y-m-d'（省略は今週）
    Route::get('/review/{week?}', [ReviewController::class, 'show'])->where('week', '\d{4}-\d{2}-\d{2}')->name('review.show');
    Route::put('/review/{week}', [ReviewController::class, 'update'])->where('week', '\d{4}-\d{2}-\d{2}')
        ->middleware('throttle:writes')->name('review.update');

    // ノーベリ帳（外れた予想の一覧）
    Route::get('/norbury', [NorburyController::class, 'index'])->name('norbury.index');

    // 屋根裏の棚。{note} は AtticNote に結びつく。直す・消すは持ち主だけ（AtticNotePolicy）
    Route::resource('attic', AtticNoteController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['attic' => 'note'])
        ->middlewareFor('store', 'throttle:writes')
        ->middlewareFor('update', ['throttle:writes', 'can:update,note'])
        ->middlewareFor('destroy', ['throttle:writes', 'can:delete,note']);
});
