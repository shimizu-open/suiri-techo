<?php

use App\Http\Controllers\Guide\GuideController;
use App\Http\Controllers\Guide\PracticeController;
use App\Http\Controllers\Guide\SaveSceneAttemptController;
use App\Http\Controllers\Guide\SceneController;
use App\Http\Controllers\Guide\SkillController;
use Illuminate\Support\Facades\Route;

/*
| 手引き（機能：Guide）。ホームズの方法を学ぶ画面。ログインして同意した人だけ。
| 住所とルート名は docs/design/basic-design.md の 2-5 のとおり。
| 練習を始める（事件を作る画面 cases.create）は事件の担当が routes/web/cases.php に書く
*/

Route::middleware(['auth', 'agreed'])->prefix('guide')->name('guide.')->group(function () {
    Route::get('/', [GuideController::class, 'index'])->name('index');
    Route::get('/reasoning', [GuideController::class, 'reasoning'])->name('reasoning');
    Route::get('/skills/{key}', [SkillController::class, 'show'])->name('skills.show');
    Route::get('/scenes', [SceneController::class, 'index'])->name('scenes.index');
    Route::get('/scenes/{key}', [SceneController::class, 'show'])->name('scenes.show');
    Route::post('/scenes/{key}/attempt', SaveSceneAttemptController::class)->middleware('throttle:writes')->name('scenes.attempt');
    Route::get('/practices', [PracticeController::class, 'index'])->name('practices.index');
});
