<?php

namespace App\Services\Review;

use App\Enums\MissReason;
use App\Enums\PredictionResult;
use App\Models\HypothesisStatusLog;
use App\Models\User;

/**
 * 週のふりかえりの数字（R-1）を数える係。数えるのはその人の記録だけ（user_id で絞る）、期間は週の月曜 0 時〜次の月曜 0 時の手前。
 * - 記録した事件：その週に作った事件の数
 * - 見立てを2つ以上：その週に作った事件のうち、見立て（自分・相棒どちらでも、消えたものも）が2つ以上ある事件の割合
 * - 答え合わせ：その週に答え合わせした予想の数と、結果ごとの数
 * - 考えを変えた：その週に見立ての状態を変えた回数（hypothesis_status_logs）
 * - 外れから学んだこと：その週の外れの「何を見落とした？」ごとの数
 * - 屋根裏の棚に増えた知識：その週に足した知識の数と、新しいものから数件
 * - 見積もりの答え合わせ：EstimateCheck に任せる
 */
class WeeklyStats
{
    /** 見積もりの答え合わせの係を受け取る */
    public function __construct(private readonly EstimateCheck $estimates) {}

    /** その人のその週の数字をまとめて返す（画面の部品にそのまま渡せる形） */
    public function for(User $user, ReviewWeek $week): array
    {
        $cases = $week->scope($user->caseFiles(), 'created_at')->withCount('hypotheses')->get(['id']);
        $multi = $cases->filter(fn ($case) => $case->hypotheses_count >= 2)->count();
        $resolved = $this->resolvedByResult($user, $week);

        $stats = [
            'cases' => $cases->count(),
            'multi_hypothesis' => ['count' => $multi, 'total' => $cases->count(), 'rate' => self::percent($multi, $cases->count())],
            'resolved' => ['total' => array_sum($resolved), 'by_result' => $resolved],
            'mind_changes' => $week->scope(HypothesisStatusLog::where('user_id', $user->id), 'created_at')->count(),
            'misses' => $this->missReasons($user, $week),
            'attic' => [
                'count' => $week->scope($user->atticNotes(), 'created_at')->count(),
                'recent' => $week->scope($user->atticNotes(), 'created_at')->latest('id')->limit(config('suiri.review.recent_attic'))->get(['id', 'rule']),
            ],
            'estimates' => $this->estimates->for($user, $week),
        ];
        $stats['is_empty'] = $stats['cases'] === 0 && $stats['resolved']['total'] === 0 && $stats['mind_changes'] === 0
            && $stats['attic']['count'] === 0 && $stats['estimates']['count'] === 0;

        return $stats;
    }

    /** 割合（％・整数）を返す。分母が 0 なら null（画面では「—」と出し、責める言葉は出さない） */
    public static function percent(int $part, int $total): ?int
    {
        return $total > 0 ? (int) round($part / $total * 100) : null;
    }

    /** その週に答え合わせした予想の数を、結果（当たり・外れ・どちらとも・前提が崩れた）ごとに返す（0 件の結果も入れる） */
    private function resolvedByResult(User $user, ReviewWeek $week): array
    {
        $counts = $week->scope($user->predictions(), 'resolved_at')->whereNotNull('result')
            ->selectRaw('result, count(*) as n')->groupBy('result')->pluck('n', 'result');
        $byResult = [];
        // 結果の種類を1つずつ取り出し、数がなければ 0 にする
        foreach (PredictionResult::cases() as $result) {
            $byResult[$result->value] = (int) ($counts[$result->value] ?? 0);
        }

        return $byResult;
    }

    /**
     * その週の外れを「何を見落とした？」ごとに数えて返す（数の多い順。0 件の種類は入れない）。
     * 見落としを選ばなかった外れは reason を null にして数える
     *
     * @return list<array{reason: ?MissReason, count: int}>
     */
    private function missReasons(User $user, ReviewWeek $week): array
    {
        $counts = $week->scope($user->predictions(), 'resolved_at')->where('result', PredictionResult::Miss->value)
            ->selectRaw('miss_reason, count(*) as n')->groupBy('miss_reason')->pluck('n', 'miss_reason');

        return $counts->map(fn ($n, $reason) => ['reason' => MissReason::tryFrom((string) $reason), 'count' => (int) $n])
            ->sortByDesc('count')->values()->all();
    }
}
