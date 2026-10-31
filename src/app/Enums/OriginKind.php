<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 事件のきっかけ（タスク・予定・練習メニュー）。case_files.origin_kind */
enum OriginKind: string
{
    use HasLabel;

    case Task = 'task';
    case Event = 'event';
    case Practice = 'practice';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'origin_kind';
    }
}
