<?php

namespace App\Queries\Ai;

use App\Models\Prediction;
use App\Models\User;

/**
 * AI の道具 list_due_predictions が返す、答え合わせ待ちの予想を集める係（読むだけ）。
 * 本人の予想のうち、まだ結果がなく取り下げてもおらず、確かめる日が「今日から $daysAhead 日後」までに来るもの。
 * 仕事の場面の事件と、相棒モードで見せ合う前の事件の予想は出さない。確かめる日の古い順
 */
class DuePredictionsQuery
{
    /**
     * 答え合わせ待ちの予想を返す（$daysAhead は 0 なら今日までに確かめる日が来たもの）
     *
     * @return array<int, array<string, mixed>>
     */
    public function for(User $user, int $daysAhead): array
    {
        $until = today()->addDays($daysAhead)->toDateString();

        return $user->predictions()
            ->whereNull('result')
            ->whereNull('withdrawn_at')
            ->where('check_on', '<=', $until)
            ->whereHas('caseFile', fn ($q) => VisibleCases::showingHypotheses(VisibleCases::scope($q)))
            ->with(['caseFile:id,title', 'hypothesis:id,body'])
            ->orderBy('check_on')->orderBy('check_at')->orderBy('id')
            ->get()
            ->map(fn (Prediction $p) => [
                ...PredictionSheet::of($p),
                'hypothesis' => $p->hypothesis?->body,
                'case' => ['id' => $p->caseFile->id, 'title' => $p->caseFile->title],
            ])->all();
    }
}
