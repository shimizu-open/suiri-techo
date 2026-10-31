<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 見立ての状態（検討中・有力・消えた・当たり）。hypotheses.status */
enum HypothesisStatus: string
{
    use HasLabel;

    case Considering = 'considering';
    case Likely = 'likely';
    case Eliminated = 'eliminated';
    case Confirmed = 'confirmed';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'hypothesis_status';
    }

    /** まだ候補として残っている見立て（消えていない）かを返す */
    public function isAlive(): bool
    {
        return $this !== self::Eliminated;
    }
}
