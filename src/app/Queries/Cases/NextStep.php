<?php

namespace App\Queries\Cases;

use App\Enums\HypothesisStatus;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\Prediction;

/**
 * 事件の詳細の一番上に出す「いまの一手」（今いちばんすべきこと）を決める係。
 * 上から順に確かめ、最初に当てはまったものを返す：閉じた → 相棒の番・自分の番 → 確かめる日が来た予想の答え合わせ →
 * 手がかりを足す → 見立てを足す → （当たりの見立てがあれば）事件を閉じる → 予想を立てる → 確かめる日を待つ。
 * 事件は clues・hypotheses・predictions を読み込んだ状態で渡す
 */
class NextStep
{
    /**
     * いまの一手を返す。key のほかに、対象の見立て（hypothesis）・予想（prediction）があれば入れる
     *
     * @return array{key: string, hypothesis?: Hypothesis, prediction?: Prediction}
     */
    public function for(CaseFile $case, ?string $turn, string $today): array
    {
        // 閉じた事件は、閉じたことを知らせて開き直しを置く
        if ($case->isClosed()) {
            return ['key' => 'closed'];
        }
        // 相棒モードで見せ合う前なら、いまの番（相棒・自分）を案内する
        if ($turn !== null) {
            return ['key' => $turn === 'self' ? 'self_turn' : 'partner_turn'];
        }

        $pending = $case->predictions->filter->isPending()->sortBy('check_on');
        $due = $pending->first(fn ($p) => $p->isDue($today));
        // 確かめる日が来た予想があれば、まず答え合わせ
        if ($due !== null) {
            return ['key' => 'resolve', 'prediction' => $due];
        }
        // 手がかりがまだなければ、まず見たこと・聞いたことを足す
        if ($case->clues->isEmpty()) {
            return ['key' => 'add_clue'];
        }
        $alive = $case->hypotheses->filter(fn ($h) => $h->status->isAlive());
        // 生きている見立てがなければ（まだない・全部消えた）、見立てを足す
        if ($alive->isEmpty()) {
            return ['key' => $case->hypotheses->isEmpty() ? 'add_hypothesis' : 'all_eliminated'];
        }
        // 答え合わせ待ちがなく、当たりの見立てがあれば、事件を閉じることを勧める
        if ($pending->isEmpty() && $alive->contains(fn ($h) => $h->status === HypothesisStatus::Confirmed)) {
            return ['key' => 'close'];
        }
        // 答え合わせ待ちの予想がなければ、有力な見立て（なければ最初の見立て）から予想を立てる
        if ($pending->isEmpty()) {
            $target = $alive->firstWhere('status', HypothesisStatus::Likely) ?? $alive->first();

            return ['key' => 'predict', 'hypothesis' => $target];
        }

        return ['key' => 'wait', 'prediction' => $pending->first()];
    }
}
