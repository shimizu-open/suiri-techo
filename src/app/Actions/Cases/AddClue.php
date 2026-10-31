<?php

namespace App\Actions\Cases;

use App\Models\CaseFile;
use App\Models\Clue;

/** 事件に手がかり（見た・聞いた・なかった）を足す用事（C-2）。あとから何度でも足せる。事件の「最近さわった日時」も新しくする */
class AddClue
{
    /** 手がかりを足して返す。持ち主（user_id）は事件から写る */
    public function handle(CaseFile $case, string $kind, string $body): Clue
    {
        $clue = $case->clues()->create(['kind' => $kind, 'body' => $body]);
        $case->touch();

        return $clue;
    }
}
