<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Support\Guide\GuideLibrary;
use App\Support\Guide\ReasoningSteps;
use App\Support\Guide\SceneAttemptReader;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * 名推理カードの一覧（GET /guide/scenes）と、カード（GET /guide/scenes/{key}）。
 * カードは表（手がかりだけ）と裏（?side=back：ホームズの推理）を別のページにし、表の HTML には推理を入れない
 */
class SceneController extends Controller
{
    /** 裏返す前の表に渡さない欄（ホームズの推理・確かめ方・答えに近い引用） */
    private const BACK_ONLY = ['reasoning', 'verification', 'quote_en'];

    /** カードの一覧を返す。自分が推理を書いたカードには印を付け、まだ書いていない最初のカードへの大きなボタンを出す */
    public function index(Request $request, GuideLibrary $library, SceneAttemptReader $attempts): View
    {
        $doneKeys = $attempts->keys($request->user());

        return view('guide.scenes.index', [
            'scenes' => $library->scenes(),
            'skillNames' => $library->skillNames(),
            'doneKeys' => $doneKeys,
            'nextScene' => $library->nextScene($doneKeys), // 「まだのカードで遊ぶ」の行き先
        ]);
    }

    /** カードを1枚見せる。ないキーなら 404。?side=back なら裏（ホームズの推理）、そうでなければ表（手がかりと自分の推理を書く欄） */
    public function show(Request $request, GuideLibrary $library, SceneAttemptReader $attempts, string $key): View
    {
        $scene = $library->scene($key) ?? abort(404);
        $back = $request->query('side') === 'back';

        return view('guide.scenes.show', [
            'scene' => $back ? $scene : Arr::except($scene, self::BACK_ONLY),
            'back' => $back,
            'steps' => $back ? ReasoningSteps::split($scene['reasoning']) : [],
            'attempt' => $attempts->find($request->user(), $key),
            'skillNames' => $library->skillNames(),
            'neighbors' => $library->neighbors('scenes', $key),
            'position' => $library->position('scenes', $key), // 何枚目か（No.07・「7 / 23 枚目」）
            'total' => count($library->scenes()),
            'mistakeNote' => $library->overview()['mistake_note'],
            'kid' => $request->user()->prefersKidWords(), // 子ども向けのヒントを開いておくか（ふだんの場面が親子の人）
        ]);
    }
}
