<?php

namespace App\Actions\Cases;

use App\Enums\HypothesisAuthor;
use App\Models\CaseFile;
use App\Models\Hypothesis;

/**
 * 事件に見立て（説明の案）を足す用事（C-3・C-11）。相棒モードの事件では、書いた人（自分・相棒）を残す。
 * 相棒モードでない事件では、書いた人はいつも「自分」にする
 */
class AddHypothesis
{
    /** 見立てを足して返す。持ち主（user_id）は事件から写る */
    public function handle(CaseFile $case, string $body, ?string $author = null): Hypothesis
    {
        $author = $case->partner_mode ? ($author ?? HypothesisAuthor::Self->value) : HypothesisAuthor::Self->value;
        $hypothesis = $case->hypotheses()->create(['body' => $body, 'author' => $author]);
        $case->touch();

        return $hypothesis;
    }
}
