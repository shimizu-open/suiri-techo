<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 外れたときの「何を見落とした？」。predictions.miss_reason */
enum MissReason: string
{
    use HasLabel;

    case FewClues = 'few_clues';
    case SingleHypothesis = 'single_hypothesis';
    case Assumption = 'assumption';
    case WrongCheck = 'wrong_check';
    case Chance = 'chance';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'miss_reason';
    }
}
