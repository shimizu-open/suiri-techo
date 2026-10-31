<?php

namespace App\Http\Controllers\Review;

use App\Actions\Review\SaveWeeklyReflection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\UpdateWeeklyReflectionRequest;
use App\Queries\Review\WeekReviewQuery;
use App\Services\Review\ReviewWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 週のふりかえり（GET /review/{week?}・PUT /review/{week}）。週は月曜日の 'Y-m-d'。省略は今週。
 * 数字・確かさの答え合わせ・週の問いを出し、週の問いを保存する
 */
class ReviewController extends Controller
{
    /** 週のふりかえりの画面を返す。月曜日でない日付は、その週の月曜日の住所へ送り直す。ない日付・未来の週は 404 */
    public function show(Request $request, WeekReviewQuery $query, ?string $week = null): View|RedirectResponse
    {
        $current = ReviewWeek::fromParam($week) ?? abort(404);
        // 住所の日付が月曜日でなければ、その週の月曜日の住所にそろえる（確かさの期間の選択は引き継ぐ）
        if ($week !== null && $week !== $current->param()) {
            return redirect()->route('review.show', ['week' => $current->param()] + $request->only('range'));
        }
        $range = $request->query('range') === 'week' ? 'week' : 'all';

        return view('review.show', $query->for($request->user(), $current, $range));
    }

    /** 週の問いを保存して、その週のふりかえりへ戻す。住所の日付が月曜日でない・未来の週なら 404 */
    public function update(UpdateWeeklyReflectionRequest $request, SaveWeeklyReflection $save, string $week): RedirectResponse
    {
        $current = ReviewWeek::fromParam($week);
        abort_if($current === null || $current->param() !== $week, 404);
        $save->handle($request->user(), $current, $request->validated());

        return redirect()->route('review.show', $current->param())->with('status', config('suiri.review.questions.saved'));
    }
}
