<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\RevealHypotheses;
use App\Http\Controllers\Cases\Concerns\RedirectsToCase;
use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use Illuminate\Http\RedirectResponse;

/** 相棒モードの事件で、隠して書いた見立てを見せ合う受付（POST /cases/{case}/reveal・C-11） */
class RevealHypothesesController extends Controller
{
    use RedirectsToCase;

    /** 見せ合って、並べて見る表のところへ戻す */
    public function __invoke(CaseFile $case, RevealHypotheses $reveal): RedirectResponse
    {
        $reveal->handle($case);

        return $this->backToCase($case, '見せ合いました。どの手がかりで見方が分かれたかを話してみましょう。', [], 'reveal');
    }
}
