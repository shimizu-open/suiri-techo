<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 答え合わせの結果（当たり・外れ・どちらとも言えない・前提が崩れた）。predictions.result */
enum PredictionResult: string
{
    use HasLabel;

    case Hit = 'hit';
    case Miss = 'miss';
    case Unclear = 'unclear';
    case Void = 'void';

    /** 表示の言葉を config/suiri.php の labels のどこから引くかを返す */
    public static function labelGroup(): string
    {
        return 'prediction_result';
    }

    /** 外れ（ノーベリ帳に載る結果）かを返す */
    public function isMiss(): bool
    {
        return $this === self::Miss;
    }
}
