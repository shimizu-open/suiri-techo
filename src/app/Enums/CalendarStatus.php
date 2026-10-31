<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** Google カレンダー連携の状態（つながっている・切れた）。calendar_connections.status */
enum CalendarStatus: string
{
    use HasLabel;

    case Active = 'active';
    case Broken = 'broken';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'calendar_status';
    }
}
