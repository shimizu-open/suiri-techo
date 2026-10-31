<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\SetFit;
use App\Enums\Fit;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\UpdateFitRequest;
use App\Models\Clue;
use App\Models\Hypothesis;
use Illuminate\Http\RedirectResponse;

/**
 * ○×の受付（C-4）：付ける（PUT /hypotheses/{hypothesis}/clues/{clue}）・外す（DELETE）。
 * 続けて付けやすいよう、その見立てのところへ戻す
 */
class FitController extends Controller
{
    use RedirectsToCase;

    /** ○（合う）か ×（合わない）を付ける */
    public function update(UpdateFitRequest $request, Hypothesis $hypothesis, Clue $clue, SetFit $setFit): RedirectResponse
    {
        $setFit->handle($hypothesis, $clue, Fit::from($request->validated('fit')));

        return $this->back($hypothesis, '○×を付けました。');
    }

    /** 付けた印を外す */
    public function destroy(UpdateFitRequest $request, Hypothesis $hypothesis, Clue $clue, SetFit $setFit): RedirectResponse
    {
        $setFit->handle($hypothesis, $clue, null);

        return $this->back($hypothesis, '○×を外しました。');
    }

    /** その見立てのところへ戻す（相棒モードで見せ合う前なら、書いた人の番の画面へ） */
    private function back(Hypothesis $hypothesis, string $status): RedirectResponse
    {
        $case = $hypothesis->caseFile;

        return $this->backToCase($case, $status, [], 'hyp-'.$hypothesis->id, $case->hidesHypotheses() ? $hypothesis->author->value : null);
    }
}
