<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** タスクの種類（ふつう・確かめる）。tasks.kind */
enum TaskKind: string
{
    use HasLabel;

    case Normal = 'normal';
    case Verify = 'verify';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'task_kind';
    }
}
