<?php

namespace App\Actions\Cases;

use App\Models\CaseFile;

/** 相棒モードの事件で、隠して書いた見立てを見せ合う用事（C-11）。見せ合った日時を残すと、互いの見立てが並んで見える */
class RevealHypotheses
{
    /** 見せ合った日時を入れる。相棒モードでない事件や、もう見せ合った事件では何もしない */
    public function handle(CaseFile $case): void
    {
        // まだ隠している相棒モードの事件だけ、見せ合った日時を入れる
        if ($case->hidesHypotheses()) {
            $case->update(['revealed_at' => now()]);
        }
    }
}
