<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\ResolvePrediction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\ResolvePredictionRequest;
use App\Models\Prediction;
use Illuminate\Http\RedirectResponse;

/** 答え合わせの受付（POST /predictions/{prediction}/result・C-7・C-8）。記録したら確かめるタスクを完了にし、同じ画面で見立ての直しを勧める */
class ResolvePredictionController extends Controller
{
    /** 答え合わせを記録して、答え合わせの画面へ戻す */
    public function __invoke(ResolvePredictionRequest $request, Prediction $prediction, ResolvePrediction $resolve): RedirectResponse
    {
        $resolve->handle($prediction, $request->validated());

        return redirect()->route('predictions.show', $prediction)->with('status', config('suiri.resolve.saved'));
    }
}
