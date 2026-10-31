<?php

namespace App\Enums\Concerns;

use App\Enums\Scene;
use App\Support\Label;

/**
 * 列挙に「表示の言葉」を持たせる共通の部品。
 * 使う列挙は labelGroup() で config/suiri.php の labels のどの種類かを返す（例 'clue_kind'）
 */
trait HasLabel
{
    /** config/suiri.php の labels の中の、この列挙の種類の名前を返す */
    abstract public static function labelGroup(): string;

    /** 画面に出す言葉を返す。場面を渡すと、その場面の言い方にする（仕事の場面など） */
    public function label(?Scene $scene = null): string
    {
        return Label::enum(static::labelGroup(), $this->value, $scene);
    }

    /** 子ども向けの言い方を返す（なければ共通の言葉） */
    public function kidLabel(): string
    {
        return Label::kidEnum(static::labelGroup(), $this->value);
    }

    /** 選択肢を「値 => 言葉」の配列で返す（<x-ui.select :options="..."> にそのまま渡せる） */
    public static function options(?Scene $scene = null): array
    {
        $options = [];
        // 列挙の値を1つずつ取り出して、値と言葉の組にする
        foreach (static::cases() as $case) {
            $options[$case->value] = $case->label($scene);
        }

        return $options;
    }

    /** 値の一覧を返す（検証の Rule::in や Rule::enum の代わりに使える） */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }
}
