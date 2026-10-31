<?php

use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

/*
| 公開ページ（機能：Legal）。ログインしていなくても見られる
*/

Route::get('/about', [LegalController::class, 'about'])->name('about');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
