<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Support\Guide\GuideLibrary;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ホームズの思考の技能の画面（GET /guide/skills/{key}）。説明・引用・練習の例・よくある失敗と、練習できる場所へのリンク */
class SkillController extends Controller
{
    /** 技能を1つ見せる。ないキーなら 404。?kid=1・?kid=0 で選んだ向き、選んでいなければふだんの場面が親子の人は子ども向けの説明を出す */
    public function show(Request $request, GuideLibrary $library, string $key): View
    {
        $skill = $library->skill($key) ?? abort(404);

        return view('guide.skills.show', [
            'skill' => $skill,
            'kid' => $request->has('kid') ? $request->boolean('kid') : $request->user()->prefersKidWords(),
            'practices' => $library->practicesFor($key),
            'scenes' => $library->scenesFor($key),
            'skillNames' => $library->skillNames(),
            'neighbors' => $library->neighbors('skills', $key),
        ]);
    }
}
