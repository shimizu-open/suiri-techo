<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\CloseCase;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\CloseCaseRequest;
use App\Models\CaseFile;
use Illuminate\Http\RedirectResponse;

/** 事件を閉じる受付（POST /cases/{case}/close・C-9）。解決か迷宮入りにし、「つまり？」があれば屋根裏の棚に入れる */
class CloseCaseController extends Controller
{
    use RedirectsToCase;

    /** 事件を閉じて、事件の詳細へ戻す */
    public function __invoke(CloseCaseRequest $request, CaseFile $case, CloseCase $closeCase): RedirectResponse
    {
        $data = $request->validated();
        $closeCase->handle($case, $data);
        $status = filled($data['rule']) ? '事件を閉じました。「つまり？」を屋根裏の棚に入れました。' : '事件を閉じました。';

        return $this->backToCase($case, $status, [$data['conclusion'], $data['rule'], $data['examples']]);
    }
}
