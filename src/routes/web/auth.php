<?php

use App\Http\Controllers\Auth\AgreementController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
| トップとログイン（機能：Auth）。ログインは Google だけ。初回は同意の画面を通る
*/

// トップ（ログイン前）。ログイン済みなら最初の画面へ送る
Route::get('/', HomeController::class)->name('home');

// Google へ送り出す・Google から戻る（ログイン前だけ。回数制限つき）
Route::middleware(['guest', 'throttle:auth'])->group(function () {
    Route::get('/auth/google', [GoogleLoginController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])->name('auth.google.callback');
});

// ログイン中の人だけ（同意の前でも使える）：ログアウトと同意
Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/agree', [AgreementController::class, 'show'])->name('agree.show');
    Route::post('/agree', [AgreementController::class, 'store'])->middleware('throttle:writes')->name('agree.store');
});
