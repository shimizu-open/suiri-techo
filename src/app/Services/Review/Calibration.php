<?php

namespace App\Services\Review;

use App\Enums\PredictionResult;
use App\Models\User;

/**
 * 確かさの答え合わせ（キャリブレーション：R-2）。確かさの区間ごとに「当たった割合」と件数を出す。
 * 数えるのは答え合わせで「当たり」か「外れ」になった予想だけ（どちらとも言えない・前提が崩れた・答え合わせ待ち・取り下げは数えない）。
 * 件数が config('suiri.calibration_min_count') より少ない区間は「まだ判断できない」（rate を null にする）
 */
class Calibration
{
    /**
     * その人の予想から区間ごとの集計を返す。週を渡すと、その週に答え合わせした予想だけ、渡さなければこれまで全部。
     *
     * @return array{buckets: list<array>, counted: int, min: int}
     */
    public function for(User $user, ?ReviewWeek $week = null): array
    {
        $query = $user->predictions()
            ->whereIn('result', [PredictionResult::Hit->value, PredictionResult::Miss->value])
            ->whereNull('withdrawn_at');
        // 週が渡されたら、その週に答え合わせした予想に絞る
        if ($week !== null) {
            $week->scope($query, 'resolved_at');
        }

        $rows = $query->get(['confidence', 'result'])
            ->map(fn ($p) => ['confidence' => (int) $p->confidence, 'hit' => $p->result === PredictionResult::Hit])
            ->all();

        return $this->summarize($rows);
    }

    /**
     * 「確かさ」と「当たったか」の組の一覧から、区間ごとの集計を作る（DB を使わないので、単体のテストで確かめられる）。
     * 区間は config('suiri.review.calibration_buckets')。区間ごとに、件数・当たり・当たった割合（％）・確かさの平均を返す
     *
     * @param  list<array{confidence: int, hit: bool}>  $rows
     * @return array{buckets: list<array>, counted: int, min: int}
     */
    public function summarize(array $rows): array
    {
        $min = (int) config('suiri.calibration_min_count');
        $buckets = [];
        // 区間を1つずつ取り出し、その区間に入る予想を数える
        foreach (config('suiri.review.calibration_buckets') as [$from, $to]) {
            $inside = array_values(array_filter($rows, fn ($r) => $r['confidence'] >= $from && $r['confidence'] <= $to));
            $count = count($inside);
            $hits = count(array_filter($inside, fn ($r) => $r['hit']));
            $buckets[] = [
                'label' => "{$from}〜{$to}％",
                'from' => $from,
                'to' => $to,
                'count' => $count,
                'hits' => $hits,
                'enough' => $count >= $min,
                // 件数が足りれば当たった割合（％）、足りなければ「まだ判断できない」の印として null
                'rate' => $count >= $min ? (int) round($hits / $count * 100) : null,
                // 図の横の位置に使う確かさの平均（1件もなければ区間のまん中）
                'mean' => $count > 0 ? array_sum(array_column($inside, 'confidence')) / $count : ($from + $to) / 2,
            ];
        }

        return ['buckets' => $buckets, 'counted' => count($rows), 'min' => $min];
    }
}
