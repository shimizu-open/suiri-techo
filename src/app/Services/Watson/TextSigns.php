<?php

namespace App\Services\Watson;

/**
 * ワトソンのひとことのために、文の中の「しるし」を探す係（推測の言葉・原因の言葉・「よくなった」・ほかの人がわかる情報）。
 * 探す言葉と形は config/watson.php の detect にあり、ここは探し方だけを持つ。文は書き換えない・どこにも送らない
 */
class TextSigns
{
    /** 推測の言葉（と思う・だろう・たぶん など）があるかを返す（W-1） */
    public function hasGuess(string $text): bool
    {
        return $this->matchesAny(config('watson.detect.guess', []), $text);
    }

    /** 原因を決める言葉（ので・から・せいで など）があるかを返す（W-2） */
    public function hasCause(string $text): bool
    {
        return $this->matchesAny(config('watson.detect.cause', []), $text);
    }

    /** 「よくなった・楽になった・改善」があるかを返す（W-4。仕事の場面で使う） */
    public function hasFeelsBetter(string $text): bool
    {
        return $this->matchesAny(config('watson.detect.work_better', []), $text);
    }

    /** ほかの人がわかる情報らしきもの（メールアドレス・電話番号・「さん」「様」のつく名前）があるかを返す（W-5） */
    public function hasPersonalInfo(string $text): bool
    {
        $text = $this->normalize($text);

        return $this->hasEmail($text) || $this->hasPhone($text) || $this->hasNamedPerson($text);
    }

    /** メールアドレスの形があるかを返す */
    private function hasEmail(string $text): bool
    {
        return (bool) preg_match('~'.config('watson.detect.email').'~u', $text);
    }

    /** 電話番号の形（区切りの記号を抜いて、0 で始まる10〜11桁）があるかを返す。日付（2026-10-31）や体温は当たらない */
    private function hasPhone(string $text): bool
    {
        $digits = preg_replace('~(?<=\d)[\-‐－−ー()（）\s](?=[\d(（])|(?<=[)）])\s*(?=\d)~u', '', $text);

        return (bool) preg_match('~'.config('watson.detect.phone').'~u', (string) $digits);
    }

    /** 漢字1〜4字かカタカナ2〜8字の名前らしきものに「さん」「様」がついているかを返す（お母さん・店員さん・Aさん は当たらない） */
    private function hasNamedPerson(string $text): bool
    {
        $suffix = config('watson.detect.name_suffix');
        $found = preg_match_all('~(?<![\p{Han}々])([\p{Han}々]{1,4})'.$suffix.'|(?<![\p{Katakana}ー])([\p{Katakana}ー]{2,8})'.$suffix.'~u', $text, $matches);
        // 見つからなければ、名前らしきものはない
        if (! $found) {
            return false;
        }

        // 見つかった言葉を1つずつ取り出し、名前ではない呼び方（お母さん・八百屋さん など）でないものが1つでもあれば当たり
        foreach (array_keys($matches[0]) as $i) {
            $word = $matches[1][$i] !== '' ? $matches[1][$i] : $matches[2][$i];
            // 名前ではない呼び方でなければ、名前らしきものと見なす
            if (! $this->isRoleWord($word)) {
                return true;
            }
        }

        return false;
    }

    /** 名前ではない呼び方（お客・先生・〜屋・〜員 など）かを返す */
    private function isRoleWord(string $word): bool
    {
        // 名前ではない呼び方の一覧にあれば、名前ではない
        if (in_array($word, config('watson.detect.not_names', []), true)) {
            return true;
        }

        return in_array(mb_substr($word, -1), config('watson.detect.not_name_endings', []), true);
    }

    /** 全角の英数字と記号を半角にそろえる（全角の＠や数字でも見つけられるように） */
    private function normalize(string $text): string
    {
        return mb_convert_kana($text, 'as');
    }

    /** 正規表現の一覧のどれか1つでも文に当てはまるかを返す */
    private function matchesAny(array $patterns, string $text): bool
    {
        // 形を1つずつ試し、当てはまったところで終える
        foreach ($patterns as $pattern) {
            // この形が文に当てはまれば、しるしがある
            if (preg_match('~'.$pattern.'~u', $text)) {
                return true;
            }
        }

        return false;
    }
}
