<?php

namespace App\Queries\Cases;

use App\Enums\CaseStatus;
use App\Enums\Scene;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 事件の一覧（C-12）を組み立てる係。状態（捜査中・解決・迷宮入り）と場面で絞り、最近さわった順に並べる。
 * 1件ごとに、手がかりと見立ての数、答え合わせ待ちの予想の数（うち確かめる日が来たもの）、答え合わせ済みの予想の数を数えておく
 */
class CaseListQuery
{
    /**
     * 一覧の画面に渡す値を返す：cases（ページ分けした事件）・status・scene・counts（状態ごとの件数）
     *
     * @return array{cases: LengthAwarePaginator, status: CaseStatus, scene: ?Scene, counts: array<string, int>}
     */
    public function handle(User $user, ?string $status, ?string $scene): array
    {
        $status = CaseStatus::tryFrom((string) $status) ?? CaseStatus::Open;
        $scene = Scene::tryFrom((string) $scene);

        $cases = self::withCounts($user->caseFiles())
            ->where('status', $status)
            ->when($scene, fn ($q) => $q->where('scene', $scene))
            ->latest('updated_at')->latest('id')
            ->paginate(config('suiri.cases.per_page'))
            ->withQueryString();

        $counts = $user->caseFiles()
            ->when($scene, fn ($q) => $q->where('scene', $scene))
            ->selectRaw('status, count(*) as total')->groupBy('status')
            ->pluck('total', 'status')->all();

        return ['cases' => $cases, 'status' => $status, 'scene' => $scene, 'counts' => $counts];
    }

    /** 事件の問い合わせに、手がかり・見立て・答え合わせ待ち・確かめる日が来た・答え合わせ済みの予想の数を足す（今日の画面でも使う） */
    public static function withCounts(Builder $query): Builder
    {
        $today = today()->toDateString();

        return $query->withCount([
            'clues',
            'hypotheses',
            'predictions as pending_predictions_count' => fn ($q) => $q->whereNull('result')->whereNull('withdrawn_at'),
            'predictions as due_predictions_count' => fn ($q) => $q->whereNull('result')->whereNull('withdrawn_at')
                ->where('check_on', '<=', $today),
            'predictions as resolved_predictions_count' => fn ($q) => $q->whereNotNull('result'),
        ]);
    }
}
