<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\CreateCase;
use App\Actions\Cases\DeleteCase;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\StoreCaseRequest;
use App\Http\Requests\Cases\UpdateCaseRequest;
use App\Models\CaseFile;
use App\Queries\Cases\CaseBoardQuery;
use App\Queries\Cases\CaseListQuery;
use App\Queries\Cases\NewCaseDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * 事件の受付（C-1・C-10・C-12）：一覧・作る画面（1問ずつ）・作る・事件の画面（いまやることのカード1枚）・なぞと場面を直す・消す。
 * 持ち主かどうかはルートの ->can() で確かめ、組み立ては Query、書き込みは Action に任せる
 */
class CaseController extends Controller
{
    use RedirectsToCase;

    /** 事件の一覧（?status=open|solved|cold&scene=daily|family|work）を返す。kid は子ども向けの言葉を出すか（ふだんの場面が親子の人） */
    public function index(Request $request, CaseListQuery $query): View
    {
        $list = $query->handle($request->user(), $request->query('status'), $request->query('scene'));

        return view('cases.index', $list + ['kid' => $request->user()->prefersKidWords()]);
    }

    /**
     * 事件を作る画面を返す。きっかけを初めの値にする：練習メニューは ?origin=practice&practice=、
     * 予定・タスクの「気になる」は POST /cases/draft から一度きりの入力（origin・label）で届く。
     * 題名の写し（label）は URL からは受け取らない（題名を URL とアクセスの記録に残さないため）
     */
    public function create(Request $request, NewCaseDraft $draft): View
    {
        return view('cases.create-steps', ['draft' => $draft->for($request->user(), $request->old() + Arr::only($request->query(), ['origin', 'practice']))]);
    }

    /** 事件を作って、その事件の詳細へ進む（最初の手がかり・見立ても一緒に保存する） */
    public function store(StoreCaseRequest $request, CreateCase $createCase): RedirectResponse
    {
        $data = $request->validated();
        $case = $createCase->handle($request->user(), $data);

        return $this->backToCase($case, '事件を作りました。', [$data['title'], $data['clue_body'], $data['hypothesis_body']]);
    }

    /** 事件の画面（いまやることのカード1枚）を返す（?turn=partner|self は相棒モードの番、?open= はカードにする用事） */
    public function show(Request $request, CaseFile $case, CaseBoardQuery $board): View
    {
        $alert = $request->session()->get('watson_alert');

        return view('cases.board', $board->handle($case, $request->user(), $request->query('turn'), $request->query('open'), $alert));
    }

    /** なぞと場面を直して、事件の詳細へ戻す */
    public function update(UpdateCaseRequest $request, CaseFile $case): RedirectResponse
    {
        $case->update($request->validated());

        return $this->backToCase($case, 'なぞと場面を直しました。', [$case->title]);
    }

    /** 事件を消して、一覧へ戻す（確かめるタスクも一緒に消す） */
    public function destroy(CaseFile $case, DeleteCase $deleteCase): RedirectResponse
    {
        $deleteCase->handle($case);

        return redirect()->route('cases.index')->with('status', '事件を消しました。');
    }
}
