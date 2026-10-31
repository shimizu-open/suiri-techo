<?php

namespace App\Queries\Review;

use App\Enums\MissReason;
use App\Enums\PredictionResult;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * ノーベリ帳（R-4）の中身を組み立てる：その人の外れた予想の一覧（答え合わせの新しい順）と、「何を見落とした？」ごとの数
 */
class NorburyQuery
{
    /** 外れた予想の一覧を返す（事件のなぞも一緒に読む）。1ページの件数は config('suiri.review.norbury_per_page') */
    public function misses(User $user): LengthAwarePaginator
    {
        return $user->predictions()
            ->where('result', PredictionResult::Miss->value)
            ->with('caseFile:id,user_id,title,scene')
            ->latest('resolved_at')->latest('id')
            ->paginate(config('suiri.review.norbury_per_page'));
    }

    /**
     * 「何を見落とした？」の種類ごとの数を返す（列挙の順。0 件の種類も入れる）。選ばなかった外れの数は unknown に入れる
     *
     * @return array{reasons: list<array{reason: MissReason, count: int}>, unknown: int, total: int}
     */
    public function reasonCounts(User $user): array
    {
        $counts = $user->predictions()
            ->where('result', PredictionResult::Miss->value)
            ->selectRaw('miss_reason, count(*) as n')->groupBy('miss_reason')
            ->pluck('n', 'miss_reason');

        $reasons = [];
        // 見落としの種類を1つずつ取り出し、数がなければ 0 にする
        foreach (MissReason::cases() as $reason) {
            $reasons[] = ['reason' => $reason, 'count' => (int) ($counts[$reason->value] ?? 0)];
        }

        return ['reasons' => $reasons, 'unknown' => (int) ($counts[''] ?? 0), 'total' => (int) $counts->sum()];
    }
}
