<?php

namespace App\Queries\Review;

use App\Models\User;
use App\Services\Review\Calibration;
use App\Services\Review\ReviewWeek;
use App\Services\Review\WeeklyStats;

/**
 * 週のふりかえりの画面に出すものをまとめて組み立てる：週の数字・確かさの答え合わせ・保存した週の問い・事件の選択肢。
 * 受付（ReviewController）を薄く保つための窓口
 */
class WeekReviewQuery
{
    /** 数える係を受け取る */
    public function __construct(
        private readonly WeeklyStats $stats,
        private readonly Calibration $calibration,
        private readonly WeekCasesQuery $cases,
    ) {}

    /**
     * 画面に渡す値を返す。range は確かさの答え合わせの期間（'all' これまで全部・'week' この週）
     */
    public function for(User $user, ReviewWeek $week, string $range): array
    {
        $reflection = $user->weeklyReflections()->where('week_start', $week->param())->first();

        return [
            'week' => $week,
            'stats' => $this->stats->for($user, $week),
            'range' => $range,
            'calibration' => $this->calibration->for($user, $range === 'week' ? $week : null),
            'reflection' => $reflection,
            'caseOptions' => $this->cases->options($user, $week, $reflection?->changed_case_file_id),
            'kid' => $user->prefersKidWords(),
        ];
    }
}
