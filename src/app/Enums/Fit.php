<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 手がかりが見立てに合うか（○ 合う・× 合わない）。clue_hypothesis.fit */
enum Fit: string
{
    use HasLabel;

    case Fits = 'fits';
    case Contradicts = 'contradicts';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'fit';
    }

    /** 記号（○ か ×）を返す */
    public function symbol(): string
    {
        return $this === self::Fits ? '○' : '×';
    }
}
