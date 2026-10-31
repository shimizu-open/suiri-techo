<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Queries\Review\NorburyQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ノーベリ帳（GET /norbury）。外れた予想の一覧と、「何を見落とした？」ごとの数を出す */
class NorburyController extends Controller
{
    /** ノーベリ帳の画面を返す。ふだんの場面が親子なら、子ども向けの呼び名（まちがいノート）も出す */
    public function index(Request $request, NorburyQuery $query): View
    {
        $user = $request->user();

        return view('norbury.index', [
            'misses' => $query->misses($user),
            'counts' => $query->reasonCounts($user),
            'kid' => $user->prefersKidWords(),
        ]);
    }
}
