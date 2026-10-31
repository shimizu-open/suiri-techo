<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Services\Guide\DailyPractice;
use App\Support\Guide\GuideLibrary;
use App\Support\Guide\SceneAttemptReader;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 手引きの入口（GET /guide）と、演繹・帰納・アブダクションの説明（GET /guide/reasoning）。
 * 中身は教材のファイル（database/data/guide/*.json）から GuideLibrary が読む
 */
class GuideController extends Controller
{
    /**
     * 手引きの入口を返す：いちばん大きく「名推理カードで遊ぶ」（まだ推理を書いていないカード）、次に今日の練習・10のわざ・3つの考え方、
     * その下にこのアプリの流れがホームズのどのわざに当たるか。ふだんの場面が親子の人には、わざの子ども向けの名前を先に出す
     */
    public function index(Request $request, GuideLibrary $library, SceneAttemptReader $attempts, DailyPractice $daily): View
    {
        $doneKeys = $attempts->keys($request->user());
        $nextScene = $library->nextScene($doneKeys);

        return view('guide.index', [
            'overview' => $library->overview(),
            'skills' => array_column($library->skills(), null, 'key'),
            'scenes' => array_column($library->scenes(), null, 'key'),
            'reasoningNames' => array_column($library->reasoning()['items'], 'name', 'key'),
            'nextScene' => $nextScene,
            'nextNumber' => $nextScene ? $library->position('scenes', $nextScene['key']) : null,
            'nextFresh' => $nextScene && ! in_array($nextScene['key'], $doneKeys, true), // まだ書いていないカードか（全部書いたら最初に戻る）
            'practice' => $daily->for($request->user()),
            'kid' => $request->user()->prefersKidWords(),
        ]);
    }

    /** 演繹・帰納・アブダクションの説明を返す。?kid=1・?kid=0 で選んだ向き、選んでいなければふだんの場面が親子の人は子ども向け */
    public function reasoning(Request $request, GuideLibrary $library): View
    {
        return view('guide.reasoning', [
            'reasoning' => $library->reasoning(),
            'scenes' => array_column($library->scenes(), null, 'key'),
            'kid' => $request->has('kid') ? $request->boolean('kid') : $request->user()->prefersKidWords(),
        ]);
    }
}
