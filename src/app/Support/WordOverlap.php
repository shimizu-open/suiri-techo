<?php

namespace App\Support;

/**
 * 2つの文の「言葉が重なるか」を単純な決まりで判定する係（提案 S-4・S-5 で使う。AI ではない）。
 * 日本語は単語の区切りがないので、意味を持ちやすい文字（漢字・カタカナ・英数字）のひと続きを取り出し、
 * 漢字は2文字ずつ、カタカナと英数字は3文字ずつのかけらに分けて、同じかけらがいくつあるかを数える。
 * ひらがな（てにをは・送りがな）は見ない。「体操着」と「体育」は「体」の1文字しか同じでないので重ならない。
 * どこにでも出てくるかけら（今日・時間 など。config('suiri.suggest.stop_words')）は数えない
 */
class WordOverlap
{
    /** 漢字のかけらの長さ */
    private const HAN_SIZE = 2;

    /** カタカナ・英数字のかけらの長さ（短いと「ミー」「ング」のような意味のない重なりが増えるので長めにする） */
    private const OTHER_SIZE = 3;

    /**
     * 文（いくつでも）から言葉のかけらを取り出し、かけらをキーにした配列で返す（数えやすくするため）
     *
     * @return array<string, true>
     */
    public static function grams(string ...$texts): array
    {
        $stop = array_flip(config('suiri.suggest.stop_words', []));
        $grams = [];
        // 文を1つずつ、意味を持ちやすい文字のひと続きに分ける
        foreach ($texts as $text) {
            // 全角の英数字は半角に、半角のカタカナは全角にそろえ、英字は小文字にしてから探す
            $normalized = mb_strtolower(mb_convert_kana($text, 'asKV'));
            preg_match_all('/\p{Han}+|[\p{Katakana}ー]+|[a-z0-9]+/u', $normalized, $runs);
            // ひと続きを1つずつ、決まった長さのかけらにする
            foreach ($runs[0] as $run) {
                $size = preg_match('/^\p{Han}/u', $run) ? self::HAN_SIZE : self::OTHER_SIZE;
                $chars = mb_str_split($run);
                // となり合う文字をかけらの長さずつ取り出す（どこにでも出てくるかけらは入れない）
                for ($i = 0; $i + $size <= count($chars); $i++) {
                    $gram = implode('', array_slice($chars, $i, $size));
                    // どこにでも出てくるかけらでなければ、かけらの一覧に入れる
                    if (! isset($stop[$gram])) {
                        $grams[$gram] = true;
                    }
                }
            }
        }

        return $grams;
    }

    /**
     * 文が、先に取り出したかけらといくつ重なるかを返す（0 なら重ならない）
     *
     * @param  array<string, true>  $grams  grams() で取り出したかけら
     */
    public static function score(array $grams, string $text): int
    {
        return count(array_intersect_key(self::grams($text), $grams));
    }
}
