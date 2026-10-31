<?php

namespace App\Http\Controllers\Cases;

use App\Actions\Cases\DismissHint;
use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use Illuminate\Http\RedirectResponse;

/** ワトソンのひとことを閉じる受付（POST /cases/{case}/hints/{rule}）。その事件では、同じ規則のひとことをもう出さない */
class DismissHintController extends Controller
{
    /** ひとことを閉じて、事件の詳細へ戻す（規則のキーはルートで config/watson.php にあるものだけに絞ってある） */
    public function __invoke(CaseFile $case, string $rule, DismissHint $dismiss): RedirectResponse
    {
        $dismiss->handle($case, $rule);

        return redirect()->route('cases.show', $case);
    }
}
