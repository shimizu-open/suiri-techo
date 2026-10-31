<?php

namespace App\Support\Guide;

/**
 * 名推理カードの「ホームズの推理」（scenes.json の reasoning）を、「事実：」「推理：」「結論：」の段に分ける係。
 * 裏返した画面で、段ごとに見出しを付けて読みやすく並べるために使う
 */
class ReasoningSteps
{
    /** 分ける目印（文の中の書き方）と、画面に出す見出し */
    private const MARKS = ['事実：' => '事実', '推理：' => '推理', '結論：' => '結論'];

    /**
     * 推理の文を段に分けて返す。目印が1つもなければ、見出しなしの1段にする
     *
     * @return list<array{label: ?string, text: string}>
     */
    public static function split(string $reasoning): array
    {
        $pattern = '/('.implode('|', array_map('preg_quote', array_keys(self::MARKS))).')/u';
        $parts = preg_split($pattern, $reasoning, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $steps = [];
        $label = null;

        // 区切った部分を1つずつ見て、目印なら次の段の見出しにし、文なら今の見出しを付けて段にする
        foreach ($parts as $part) {
            // 目印（「事実：」など）なら、次に来る文の見出しとして覚える
            if (isset(self::MARKS[$part])) {
                $label = self::MARKS[$part];

                continue;
            }
            $text = trim($part);
            // 空白だけの部分は段にしない
            if ($text !== '') {
                $steps[] = ['label' => $label, 'text' => $text];
            }
        }

        return $steps;
    }
}
