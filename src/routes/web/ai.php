<?php

use App\Http\Controllers\Settings\RevokeAiAppController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;

/*
| ChatGPT・Claude とつなぐ（機能：AI とつなぐ）のうち、利用者のブラウザで開く住所。ログインして利用規約に同意した人だけ。
| 同意の画面（/oauth/authorize）は Passport の受付をそのまま使い、ルートの名前も Passport と同じにする
| （laravel/mcp の案内の中身が route('passport.authorizations.authorize') で作られ、埋め込み禁止の札もこの名前で付くため）。
| ログインしていない人は Google のログインへ（bootstrap/app.php の redirectGuestsTo）、同意前の人は /agree へ送ってから、ここへ戻る。
| AI の側の住所（案内・自己登録・通行証・窓口）は routes/ai.php
*/

Route::middleware(['auth', 'agreed'])->group(function () {
    // 同意の画面「ChatGPT に、あなたの事件の記録を読ませてよいですか？」（画面は views/oauth/authorize）
    Route::get('/oauth/authorize', [AuthorizationController::class, 'authorize'])->name('passport.authorizations.authorize');

    Route::middleware('throttle:writes')->group(function () {
        // 許可する・やめる（同意の画面のボタン）
        Route::post('/oauth/authorize', [ApproveAuthorizationController::class, 'approve'])->name('passport.authorizations.approve');
        Route::delete('/oauth/authorize', [DenyAuthorizationController::class, 'deny'])->name('passport.authorizations.deny');

        // 設定の「つなぐのをやめる」：その AI の通行証と更新の札をすべて取り消す（{app} は AI の登録の番号）
        Route::delete('/settings/ai/{app}', RevokeAiAppController::class)->whereUuid('app')->name('settings.ai.revoke');
    });
});
