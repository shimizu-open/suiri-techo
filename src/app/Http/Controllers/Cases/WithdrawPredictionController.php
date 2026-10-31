<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\WithdrawPrediction;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\WithdrawPredictionRequest;
use App\Models\Prediction;
use Illuminate\Http\RedirectResponse;

/**
 * 予想を取り下げる受付（POST /predictions/{prediction}/withdraw）。文と確かさは残し、済んでいない確かめるタスクは消す。
 * 答え合わせ待ちでない予想は WithdrawPredictionRequest が 403 で断る
 */
class WithdrawPredictionController extends Controller
{
    use RedirectsToCase;

    /** 予想を取り下げて、その見立てのところへ戻す */
    public function __invoke(WithdrawPredictionRequest $request, Prediction $prediction, WithdrawPrediction $withdraw): RedirectResponse
    {
        $withdraw->handle($prediction);

        return $this->backToCase($prediction->caseFile, '予想を取り下げました。確かめるタスクも消しました。', [], 'hyp-'.$prediction->hypothesis_id);
    }
}
