<?php

use App\Http\Controllers\Account\DeleteAccountController;
use App\Http\Controllers\Account\ExportUserDataController;
use App\Http\Controllers\Settings\SettingsController;
use Illuminate\Support\Facades\Route;

/*
| 設定（機能：Settings）。ログインして同意した人だけ。
| 表示名・相棒・ふだんの場面・記録のきっかけ は土台の担当。
| データのダウンロード（settings.export）と退会（account.destroy）は、ふりかえり・退会の担当がこの下に足す
*/

Route::middleware(['auth', 'agreed'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::patch('/settings', [SettingsController::class, 'update'])->middleware('throttle:writes')->name('settings.update');

    // ここから下：ふりかえり・退会の担当（GET /settings/export → settings.export、DELETE /account → account.destroy）
    Route::get('/settings/export', ExportUserDataController::class)->middleware('throttle:account')->name('settings.export');
    Route::delete('/account', DeleteAccountController::class)->middleware('throttle:account')->name('account.destroy');
});
