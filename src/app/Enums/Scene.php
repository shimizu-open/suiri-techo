<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 場面（日常・親子・仕事）。言葉の出し分けに使う。users.default_scene・case_files.scene */
enum Scene: string
{
    use HasLabel;

    case Daily = 'daily';
    case Family = 'family';
    case Work = 'work';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'scene';
    }
}
