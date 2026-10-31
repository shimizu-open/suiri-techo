<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 事件の状態（捜査中・解決・迷宮入り）。case_files.status */
enum CaseStatus: string
{
    use HasLabel;

    case Open = 'open';
    case Solved = 'solved';
    case Cold = 'cold';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'case_status';
    }

    /** 閉じた事件（解決・迷宮入り）かを返す */
    public function isClosed(): bool
    {
        return $this !== self::Open;
    }
}
