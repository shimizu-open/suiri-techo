<?php

use App\Http\Controllers\Cases\CaseController;
use App\Http\Controllers\Cases\CloseCaseController;
use App\Http\Controllers\Cases\ClueController;
use App\Http\Controllers\Cases\DismissHintController;
use App\Http\Controllers\Cases\DraftCaseController;
use App\Http\Controllers\Cases\FitController;
use App\Http\Controllers\Cases\HypothesisController;
use App\Http\Controllers\Cases\PredictionController;
use App\Http\Controllers\Cases\ReopenCaseController;
use App\Http\Controllers\Cases\ResolvePredictionController;
use App\Http\Controllers\Cases\RevealHypothesesController;
use App\Http\Controllers\Cases\WithdrawPredictionController;
use Illuminate\Support\Facades\Route;

/*
| 事件・手がかり・見立て・予想（機能：Cases）。住所とルート名は docs/design/basic-design.md の 2-4 のとおり。
| ログインして同意した人だけ。持ち主かは ->can() で確かめる（他人の記録は 403）。書き込みには throttle:writes。
| 手がかり・見立て・○×・予想・見せ合うは ->can('write', …)：持ち主で、事件が捜査中のときだけ（閉じた事件は「開き直してください」の 403）。
| 予想の文と確かさを直すルートは作らない（後知恵を防ぐ決まり）
*/

Route::middleware(['auth', 'agreed'])->group(function () {
    // 事件：一覧・作る・詳細・なぞと場面を直す・消す
    Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [CaseController::class, 'create'])->name('cases.create');
    // 予定・タスクの「気になる」：題名を URL に載せずに、作る画面へ持っていく（まだ作らない）
    Route::post('/cases/draft', DraftCaseController::class)->name('cases.draft');
    Route::get('/cases/{case}', [CaseController::class, 'show'])->can('view', 'case')->name('cases.show');

    Route::middleware('throttle:writes')->group(function () {
        Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
        Route::patch('/cases/{case}', [CaseController::class, 'update'])->can('update', 'case')->name('cases.update');
        Route::delete('/cases/{case}', [CaseController::class, 'destroy'])->can('delete', 'case')->name('cases.destroy');

        // 閉じる・開き直す・見せ合う
        Route::post('/cases/{case}/close', CloseCaseController::class)->can('update', 'case')->name('cases.close');
        Route::delete('/cases/{case}/close', ReopenCaseController::class)->can('update', 'case')->name('cases.reopen');
        Route::post('/cases/{case}/reveal', RevealHypothesesController::class)->can('write', 'case')->name('cases.reveal');

        // 手がかり（閉じた事件には足せない・直せない・消せない）
        Route::post('/cases/{case}/clues', [ClueController::class, 'store'])->can('write', 'case')->name('clues.store');
        Route::patch('/clues/{clue}', [ClueController::class, 'update'])->can('write', 'clue')->name('clues.update');
        Route::delete('/clues/{clue}', [ClueController::class, 'destroy'])->can('write', 'clue')->name('clues.destroy');

        // 見立て（閉じた事件には足せない・直せない・消せない）
        Route::post('/cases/{case}/hypotheses', [HypothesisController::class, 'store'])->can('write', 'case')->name('hypotheses.store');
        Route::patch('/hypotheses/{hypothesis}', [HypothesisController::class, 'update'])->can('write', 'hypothesis')->name('hypotheses.update');
        Route::delete('/hypotheses/{hypothesis}', [HypothesisController::class, 'destroy'])->can('write', 'hypothesis')->name('hypotheses.destroy');

        // ○×（見立てと手がかりの両方が自分のもので、事件が捜査中か確かめる。同じ事件かは UpdateFitRequest）
        Route::put('/hypotheses/{hypothesis}/clues/{clue}', [FitController::class, 'update'])
            ->can('write', 'hypothesis')->can('write', 'clue')->name('fits.update');
        Route::delete('/hypotheses/{hypothesis}/clues/{clue}', [FitController::class, 'destroy'])
            ->can('write', 'hypothesis')->can('write', 'clue')->name('fits.destroy');

        // 予想：立てる（捜査中の事件だけ）・答え合わせ・取り下げ。
        // 答え合わせと取り下げは閉じた事件でもできる（確かめる日を待たずに閉じても、待っている予想の結果は書けるように。
        // 結果を書かないとノーベリ帳と確かさの集計に入らず、取り下げないと確かめるタスクが残り続けるため）
        Route::post('/hypotheses/{hypothesis}/predictions', [PredictionController::class, 'store'])->can('write', 'hypothesis')->name('predictions.store');
        Route::post('/predictions/{prediction}/result', ResolvePredictionController::class)->can('update', 'prediction')->name('predictions.resolve');
        Route::post('/predictions/{prediction}/withdraw', WithdrawPredictionController::class)->can('update', 'prediction')->name('predictions.withdraw');

        // ワトソンのひとことを閉じる（規則のキーは config/watson.php にあるものだけ）
        Route::post('/cases/{case}/hints/{rule}', DismissHintController::class)->can('update', 'case')
            ->whereIn('rule', array_keys(config('watson.rules', [])))->name('hints.dismiss');
    });

    // 答え合わせの画面
    Route::get('/predictions/{prediction}', [PredictionController::class, 'show'])->can('view', 'prediction')->name('predictions.show');
});
