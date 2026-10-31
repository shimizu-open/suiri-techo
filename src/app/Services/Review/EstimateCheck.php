<?php

namespace App\Services\Review;

use App\Models\User;

/**
 * 見積もりの答え合わせ（R-1）を数える係。
 * その週に完了したタスクのうち、予想時間（duration_minutes）とかかった時間（actual_minutes）の両方があるものを数え、
 * 件数・予想より長かった割合（％）・平均のずれ（かかった時間 − 予想時間 の平均。分・整数、長ければ正）を返す
 */
class EstimateCheck
{
    /** その人のその週の見積もりの答え合わせを返す。件数が 0 なら割合とずれは null */
    public function for(User $user, ReviewWeek $week): array
    {
        $tasks = $week->scope($user->tasks(), 'done_at')
            ->whereNotNull('duration_minutes')->whereNotNull('actual_minutes')
            ->get(['duration_minutes', 'actual_minutes']);

        $count = $tasks->count();
        $longer = $tasks->filter(fn ($task) => $task->actual_minutes > $task->duration_minutes)->count();
        $diffs = $tasks->map(fn ($task) => $task->actual_minutes - $task->duration_minutes);

        return [
            'count' => $count,
            'longer' => $longer,
            'longer_rate' => WeeklyStats::percent($longer, $count),
            'average_diff' => $count > 0 ? (int) round($diffs->avg()) : null,
        ];
    }
}
