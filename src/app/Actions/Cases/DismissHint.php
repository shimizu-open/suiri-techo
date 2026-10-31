<?php

namespace App\Actions\Cases;

use App\Models\CaseFile;

/** ワトソンのひとことを閉じる用事。閉じた規則を事件ごとに残し、その事件ではもう出さない（同じ規則を2回閉じても1行） */
class DismissHint
{
    /** 規則（config/watson.php の rules のキー）を閉じた記録を残す */
    public function handle(CaseFile $case, string $rule): void
    {
        $case->dismissedHints()->firstOrCreate(['rule' => $rule]);
    }
}
