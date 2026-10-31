<?php

namespace App\Actions\Cases;

use App\Enums\CaseStatus;
use App\Models\CaseFile;

/** 閉じた事件を開き直す用事（捜査中に戻す）。結論は消さずに残す（前の考えの記録として） */
class ReopenCase
{
    /** 事件を捜査中に戻し、閉じた日時を空にする */
    public function handle(CaseFile $case): void
    {
        $case->update(['status' => CaseStatus::Open, 'closed_at' => null]);
    }
}
