<?php

namespace App\Queries\Ai;

use App\Enums\Fit;
use App\Models\CaseFile;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use App\Models\Prediction;

/**
 * AI の道具 get_case が返す、1つの事件の中身を組み立てる係（読むだけ）。
 * なぞ・手がかり・見立て（合う／合わない手がかりの番号つき）・見立てごとの予想と答え合わせ。
 * 相棒モードで見せ合う前の事件は、見立てと予想を空にして、隠していることを一言で伝える
 */
class CaseSheetQuery
{
    /** 事件の中身を配列で返す（事件はすでに本人の・仕事の場面でないものに絞ってある） */
    public function for(CaseFile $case): array
    {
        $case->load(['clues', 'hypotheses.fits', 'hypotheses.predictions' => fn ($q) => $q->oldest('id')]);
        $hidden = $case->hidesHypotheses();

        return [
            'case' => [
                'id' => $case->id,
                'title' => $case->title,
                'status' => $case->status->value,
                'status_label' => $case->status->label(),
                'scene' => $case->scene->label(),
                'partner_mode' => $case->partner_mode,
                'conclusion' => $case->conclusion,
                'created_at' => $case->created_at?->toIso8601String(),
                'closed_at' => $case->closed_at?->toIso8601String(),
                'url' => route('cases.show', $case),
            ],
            'clues' => $case->clues->map(fn ($clue) => [
                'id' => $clue->id,
                'kind' => $clue->kind->value,
                'kind_label' => $clue->kind->label(),
                'body' => $clue->body,
                'created_at' => $clue->created_at?->toIso8601String(),
            ])->all(),
            'hypotheses' => $hidden ? [] : $case->hypotheses->map(fn (Hypothesis $h) => $this->hypothesis($h))->all(),
            'hidden_note' => $hidden ? config('ai_connect.messages.hidden_note') : null,
        ];
    }

    /** 見立て1つを、合う／合わない手がかりの番号と予想の一覧つきの配列にする */
    private function hypothesis(Hypothesis $hypothesis): array
    {
        $clueIds = fn (Fit $fit) => $hypothesis->fits->filter(fn (ClueHypothesis $row) => $row->fit === $fit)->pluck('clue_id')->values()->all();

        return [
            'id' => $hypothesis->id,
            'body' => $hypothesis->body,
            'status' => $hypothesis->status->value,
            'status_label' => $hypothesis->status->label(),
            'author' => $hypothesis->author->label(),
            'fits' => $clueIds(Fit::Fits),
            'contradicts' => $clueIds(Fit::Contradicts),
            'predictions' => $hypothesis->predictions->map(fn (Prediction $p) => PredictionSheet::of($p))->all(),
        ];
    }
}
