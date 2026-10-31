<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\AddClue;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\StoreClueRequest;
use App\Http\Requests\Cases\UpdateClueRequest;
use App\Models\CaseFile;
use App\Models\Clue;
use Illuminate\Http\RedirectResponse;

/**
 * 手がかりの受付（C-2）：足す（POST /cases/{case}/clues）・直す（PATCH /clues/{clue}）・消す（DELETE /clues/{clue}）。
 * 保存した文にほかの人がわかる情報らしきものがあれば、事件の詳細でワトソンの警告を出す（保存は止めない）
 */
class ClueController extends Controller
{
    use RedirectsToCase;

    /** 手がかりを足して、事件の詳細の手がかりのところへ戻す */
    public function store(StoreClueRequest $request, CaseFile $case, AddClue $addClue): RedirectResponse
    {
        $clue = $addClue->handle($case, $request->validated('kind'), $request->validated('body'));

        return $this->backToCase($case, '手がかりを足しました。', [$clue->body], 'clues');
    }

    /** 手がかりを直して、事件の詳細へ戻す */
    public function update(UpdateClueRequest $request, Clue $clue): RedirectResponse
    {
        $clue->update($request->validated());

        return $this->backToCase($clue->caseFile, '手がかりを直しました。', [$clue->body], 'clues');
    }

    /** 手がかりを消して、事件の詳細へ戻す（付けていた ○× も一緒に消える） */
    public function destroy(Clue $clue): RedirectResponse
    {
        $case = $clue->caseFile;
        $clue->delete();

        return $this->backToCase($case, '手がかりを消しました。', [], 'clues');
    }
}
