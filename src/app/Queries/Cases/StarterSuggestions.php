<?php

namespace App\Queries\Cases;

use App\Enums\Scene;
use Illuminate\Support\Str;

/**
 * 決まった型から「書き出し」を選ぶ係（提案 S-1〜S-3。AI ではなく config/suiri.php の suggest の言葉を出し分けるだけ）。
 * 手がかりの問いかけ・別の説明の型・予想の穴埋め・屋根裏の知識の書き出しを返す。
 * 出し分け：事件の場面が仕事なら仕事の型を、そうでなく、ふだんの場面が親子の人（kid）なら子ども向けの言い方を使う
 */
class StarterSuggestions
{
    /**
     * 手がかりを足す欄の問いかけを返す（1件：label・text・kind。kind は押したときに切り替える種類、なければ null）
     *
     * @return list<array{label: string, text: string, kind: ?string}>
     */
    public function clueQuestions(?Scene $scene): array
    {
        $set = $scene === Scene::Work ? 'work' : 'default';

        return array_map(fn (array $q) => $q + ['kind' => null], config("suiri.suggest.clue_questions.{$set}"));
    }

    /**
     * 見立てを足す欄の「別の説明の型」を返す（1件：label・hint・text）。仕事の場面はワトソンの W-4 と同じ5つ
     *
     * @return list<array{label: string, hint: string, text: string}>
     */
    public function hypothesisTypes(?Scene $scene, bool $kid): array
    {
        // 場面とふだんの場面で型を選ぶ：仕事の事件 → 仕事の型、親子の人 → 子ども向け、ほか → ふだんの型
        $set = match (true) {
            $scene === Scene::Work => 'work',
            $kid => 'kid',
            default => 'default',
        };

        return config("suiri.suggest.hypothesis_types.{$set}");
    }

    /** 見立ての数がこれ以下なら、別の説明の型を開いて目立たせる（見立てが0〜1個のとき） */
    public function emphasizeTypes(int $hypothesisCount): bool
    {
        return $hypothesisCount <= (int) config('suiri.suggest.hypothesis_types.emphasize_until');
    }

    /** 予想の穴埋めの文を返す（「もし『見立て』が正しいなら、＿＿のときは＿＿のはず」。仕事の場面・親子の人で言い方を変える） */
    public function predictionTemplate(string $hypothesisBody, ?Scene $scene, bool $kid): string
    {
        // 場面とふだんの場面で型を選ぶ：仕事の事件 → 「次回」つき、親子の人 → 短い言い方、ほか → ふだんの型
        $set = match (true) {
            $scene === Scene::Work => 'work',
            $kid => 'kid',
            default => 'default',
        };

        return str_replace(':hypothesis', $this->quote($hypothesisBody), config("suiri.suggest.prediction_template.{$set}"));
    }

    /** 屋根裏の知識を見立ての欄に入れるときの書き出しを返す（「前にわかった『〜』が当てはまるなら、」） */
    public function atticStarter(string $rule): string
    {
        return str_replace(':rule', $this->quote($rule), config('suiri.suggest.attic.hypothesis_text'));
    }

    /** 書き出しに写す本文を1行にして、長ければ「…」で切る */
    private function quote(string $text): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $text)), (int) config('suiri.suggest.quote_length'), '…');
    }
}
