<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\AddHypothesis;
use App\Actions\Cases\ChangeHypothesisStatus;
use App\Actions\Cases\DeleteHypothesis;
use App\Enums\HypothesisStatus;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\StoreHypothesisRequest;
use App\Http\Requests\Cases\UpdateHypothesisRequest;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use Illuminate\Http\RedirectResponse;

/**
 * 見立ての受付（C-3・C-5・C-11）：足す（POST /cases/{case}/hypotheses）・直す（PATCH /hypotheses/{hypothesis}。本文か状態）・
 * 消す（DELETE）。状態を変えたら「考えを変えた記録」が残る。相棒モードで見せ合う前は、書いた人の番の画面へ戻す
 */
class HypothesisController extends Controller
{
    use RedirectsToCase;

    /** 見立てを足して、事件の詳細の見立てのところへ戻す */
    public function store(StoreHypothesisRequest $request, CaseFile $case, AddHypothesis $addHypothesis): RedirectResponse
    {
        $hypothesis = $addHypothesis->handle($case, $request->validated('body'), $request->validated('author'));

        return $this->backToCase($case, '見立てを足しました。', [$hypothesis->body], 'hypotheses', $this->turnOf($hypothesis));
    }

    /** 見立ての本文を直すか、状態を変えて（記録を残して）、その見立てのところへ戻す */
    public function update(UpdateHypothesisRequest $request, Hypothesis $hypothesis, ChangeHypothesisStatus $changeStatus): RedirectResponse
    {
        // 本文が送られていれば、本文を直す
        if ($request->has('body')) {
            $hypothesis->update(['body' => $request->validated('body')]);
        }
        // 状態が送られていれば、状態を変えて記録を残す
        if ($request->has('status')) {
            $changeStatus->handle($hypothesis, HypothesisStatus::from($request->validated('status')));
        }

        return $this->backToCase($hypothesis->caseFile, '見立てを直しました。', [$hypothesis->body], 'hyp-'.$hypothesis->id, $this->turnOf($hypothesis));
    }

    /** 見立てを消して、事件の詳細へ戻す（その見立ての予想と確かめるタスクも消える） */
    public function destroy(Hypothesis $hypothesis, DeleteHypothesis $deleteHypothesis): RedirectResponse
    {
        $case = $hypothesis->caseFile;
        $turn = $this->turnOf($hypothesis);
        $deleteHypothesis->handle($hypothesis);

        return $this->backToCase($case, '見立てを消しました。', [], 'hypotheses', $turn);
    }

    /** 相棒モードで見せ合う前なら、その見立てを書いた人の番（'partner'・'self'）を返す。それ以外は null */
    private function turnOf(Hypothesis $hypothesis): ?string
    {
        return $hypothesis->caseFile->hidesHypotheses() ? $hypothesis->author->value : null;
    }
}
