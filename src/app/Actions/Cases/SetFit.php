<?php

namespace App\Actions\Cases;

use App\Enums\Fit;
use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;

/**
 * 見立てごとに、手がかりへ ○（合う）×（合わない）を付ける・外す用事（C-4）。
 * 見立てと手がかりの組ごとに1行だけ持つ（付け直すと上書き）。$fit が null なら印を外す
 */
class SetFit
{
    /** ○× を付ける（null なら外す） */
    public function handle(Hypothesis $hypothesis, Clue $clue, ?Fit $fit): void
    {
        // 印を外すときは、その組の行を消して終わる
        if ($fit === null) {
            $hypothesis->fits()->where('clue_id', $clue->id)->delete();

            return;
        }

        ClueHypothesis::updateOrCreate(
            ['hypothesis_id' => $hypothesis->id, 'clue_id' => $clue->id],
            ['fit' => $fit],
        );
    }
}
