<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\ReopenCase;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use Illuminate\Http\RedirectResponse;

/** 閉じた事件を開き直す受付（DELETE /cases/{case}/close）。捜査中に戻す */
class ReopenCaseController extends Controller
{
    use RedirectsToCase;

    /** 事件を開き直して、事件の詳細へ戻す */
    public function __invoke(CaseFile $case, ReopenCase $reopenCase): RedirectResponse
    {
        $reopenCase->handle($case);

        return $this->backToCase($case, '事件を開き直しました。');
    }
}
