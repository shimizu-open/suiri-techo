<?php

namespace App\Queries\Ai;

use App\Enums\HypothesisStatus;
use App\Models\CaseFile;
use App\Models\User;

/**
 * AI の道具 list_cases が返す事件の一覧を組み立てる係（読むだけ）。
 * 本人の事件のうち仕事の場面を除いたもの（VisibleCases）を、状態と題名の言葉で絞り、新しく動いた順に返す。
 * 1件ごとに、手がかりの数・消えていない見立ての数・答え合わせ待ちの予想の数と、推理手帳のその画面の住所を付ける
 */
class CaseListQuery
{
    /** 見える事件の決まりを受け取る */
    public function __construct(private readonly VisibleCases $visible) {}

    /**
     * 事件の一覧を返す。$status は open・solved・cold・all、$keyword は題名に含む言葉（空なら絞らない）
     *
     * @return array<int, array<string, mixed>>
     */
    public function for(User $user, string $status, ?string $keyword, int $limit): array
    {
        $cases = $this->visible->of($user)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when(filled($keyword), fn ($q) => $q->where('title', 'like', '%'.addcslashes((string) $keyword, '%_\\').'%'))
            ->withCount([
                'clues',
                'hypotheses' => fn ($q) => $q->where('status', '!=', HypothesisStatus::Eliminated->value),
                'predictions as pending_prediction_count' => fn ($q) => $q->whereNull('result')->whereNull('withdrawn_at'),
            ])
            ->latest('updated_at')->latest('id')->limit($limit)->get();

        return $cases->map(fn (CaseFile $case) => [
            'id' => $case->id,
            'title' => $case->title,
            'status' => $case->status->value,
            'status_label' => $case->status->label(),
            'scene' => $case->scene->label(),
            'clue_count' => $case->clues_count,
            // 相棒モードで見せ合う前は、見立ての数も出さない
            'hypothesis_count' => $case->hidesHypotheses() ? null : $case->hypotheses_count,
            'pending_prediction_count' => $case->hidesHypotheses() ? null : $case->pending_prediction_count,
            'updated_at' => $case->updated_at?->toIso8601String(),
            'url' => route('cases.show', $case),
        ])->all();
    }
}
