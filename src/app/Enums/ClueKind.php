<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 手がかりの種類（見た・聞いた・なかった）。仕事の場面では言い方が変わる。clues.kind */
enum ClueKind: string
{
    use HasLabel;

    case Seen = 'seen';
    case Heard = 'heard';
    case Absent = 'absent';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'clue_kind';
    }
}
