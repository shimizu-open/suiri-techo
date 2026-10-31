<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\MakePrediction;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\StorePredictionRequest;
use App\Models\Hypothesis;
use App\Models\Prediction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * 予想の受付（C-6・C-7）：立てる（POST /hypotheses/{hypothesis}/predictions。確かめるタスクも作る）と、答え合わせの画面
 * （GET /predictions/{prediction}）。予想の文と確かさを直す画面・ルートは作らない（後知恵を防ぐ決まり）
 */
class PredictionController extends Controller
{
    use RedirectsToCase;

    /** 予想を立てて（確かめるタスクも作って）、その見立てのところへ戻す */
    public function store(StorePredictionRequest $request, Hypothesis $hypothesis, MakePrediction $makePrediction): RedirectResponse
    {
        $prediction = $makePrediction->handle($hypothesis, $request->validated());

        return $this->backToCase(
            $hypothesis->caseFile,
            '予想を立てました。確かめる日（'.Carbon::parse($prediction->check_on)->isoFormat('M月D日').'）のタスクを作りました。',
            [$prediction->statement],
            'hyp-'.$hypothesis->id,
        );
    }

    /** 答え合わせの画面を返す（最初の予想の文と書いた日時を必ず見せる）。kid は子ども向けの言葉を出すか（ふだんの場面が親子の人） */
    public function show(Request $request, Prediction $prediction): View
    {
        $prediction->load(['hypothesis', 'caseFile', 'atticNote']);

        return view('predictions.show', ['prediction' => $prediction, 'case' => $prediction->caseFile, 'kid' => $request->user()->prefersKidWords()]);
    }
}
