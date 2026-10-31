<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 見立てを書いた人（自分・相棒）。hypotheses.author */
enum HypothesisAuthor: string
{
    use HasLabel;

    case Self = 'self';
    case Partner = 'partner';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'hypothesis_author';
    }
}
