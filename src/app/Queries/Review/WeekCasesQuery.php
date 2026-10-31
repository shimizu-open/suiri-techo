<?php

namespace App\Queries\Review;

use App\Models\User;
use App\Services\Review\ReviewWeek;

/**
 * 週の問い「今週いちばん考えが変わった事件は？」の選択肢を作る。
 * その人の事件のうち、その週に「作った」「見立ての状態を変えた」「予想の答え合わせをした」のどれかがある事件（新しい順）。
 * すでに選んで保存した事件は、条件に合わなくなっても選択肢に残す
 */
class WeekCasesQuery
{
    /** 選択肢を「事件の id => なぞ」の配列で返す（<x-ui.select :options> にそのまま渡せる） */
    public function options(User $user, ReviewWeek $week, ?int $selectedId = null): array
    {
        return $user->caseFiles()
            ->where(function ($query) use ($week, $selectedId) {
                $week->scope($query, 'created_at')
                    ->orWhereHas('hypotheses.statusLogs', fn ($logs) => $week->scope($logs, 'hypothesis_status_logs.created_at'))
                    ->orWhereHas('predictions', fn ($predictions) => $week->scope($predictions, 'resolved_at'));
                // 保存済みの事件があれば、それも選択肢に入れる
                if ($selectedId !== null) {
                    $query->orWhere('id', $selectedId);
                }
            })
            ->latest('updated_at')->latest('id')
            ->limit(config('suiri.review.case_options'))
            ->pluck('title', 'id')
            ->all();
    }
}
