<?php

namespace App\Support;

use App\Enums\Scene;

/**
 * 画面の言葉を config/suiri.php の labels から引く係。
 * 場面ごとの出し分け（仕事の場面では「見た」を「見た・触れた・測った」にする など）と、子ども向けの言い方をここでそろえる。
 * 列挙の label()・kidLabel() と、列挙ではない言葉の word()・kidWord() が使う
 */
class Label
{
    /**
     * 列挙の値の言葉を返す（例：enum('clue_kind', 'seen', Scene::Work) → 「見た・触れた・測った」）。
     * 場面の出し分けがあればそれを、なければ共通の言葉を、それもなければ値そのものを返す
     */
    public static function enum(string $group, string $value, ?Scene $scene = null): string
    {
        return self::pick($group, $value, $scene);
    }

    /** 列挙の値の子ども向けの言い方を返す。子ども向けがなければ共通の言葉を返す */
    public static function kidEnum(string $group, string $value): string
    {
        return config("suiri.labels.kid.{$group}.{$value}") ?? self::pick($group, $value, null);
    }

    /**
     * 列挙ではない画面の言葉を返す（例：word('prediction', Scene::Work) → 「次回、確かめること」）。
     * 場面の出し分けがあればそれを、なければ共通の言葉を返す
     */
    public static function word(string $key, ?Scene $scene = null): string
    {
        return self::pick('words', $key, $scene);
    }

    /** 列挙ではない画面の言葉の子ども向けの言い方を返す（例：kidWord('hypothesis') → 「かも」） */
    public static function kidWord(string $key): string
    {
        return config("suiri.labels.kid.words.{$key}") ?? self::pick('words', $key, null);
    }

    /** 場面の出し分け → 共通の言葉 → 値そのもの、の順に探して返す */
    private static function pick(string $group, string $key, ?Scene $scene): string
    {
        // 場面が渡されて、その場面だけの言い方が決まっていれば、それを使う
        if ($scene !== null && ($byScene = config("suiri.labels.by_scene.{$scene->value}.{$group}.{$key}")) !== null) {
            return $byScene;
        }

        return config("suiri.labels.{$group}.{$key}") ?? $key;
    }
}
