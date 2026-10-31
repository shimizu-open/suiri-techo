<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Support\Guide\GuideLibrary;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** 練習メニューの一覧（GET /guide/practices）。「この練習を始める」で、問いかけ入りの事件を作る画面へ進む */
class PracticeController extends Controller
{
    /** 練習メニューの一覧を返す。ふだんの場面が親子の人には、子ども向けの説明を先に出す */
    public function index(Request $request, GuideLibrary $library): View
    {
        return view('guide.practices.index', [
            'practices' => $library->practices(),
            'skillNames' => $library->skillNames(),
            'kid' => $request->user()->prefersKidWords(),
        ]);
    }
}
