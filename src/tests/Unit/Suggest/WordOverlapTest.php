<?php

namespace Tests\Unit\Suggest;

use App\Support\WordOverlap;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** 言葉の重なりの判定（提案 S-4・S-5 の共通の係）。重なるべき組は重なり、まぎらわしい組は重ならないこと */
class WordOverlapTest extends TestCase
{
    /** 事件の言葉と、比べる文と、重なるはずか */
    public static function pairs(): array
    {
        return [
            '漢字2文字が同じ' => ['朝の電車がいつも遅れるのはなぜ？', '電車で出張', true],
            '長い言葉の一部が同じ' => ['算数の宿題を忘れる', '宿題を見る時間', true],
            'カタカナ3文字が同じ' => ['ランドセルが重い', 'ランドセルを買いに行く', true],
            '全角と半角の英数字をそろえる' => ['ＰＴＡの集まり', 'PTA 会議', true],
            '1文字しか同じでない（体操着と体育）' => ['体操着をなぜ忘れる？', '体育', false],
            'ひらがなだけが同じ' => ['いつもおくれる', 'いつものおみせ', false],
            'カタカナ2文字だけが同じ' => ['ミーティングに遅れる', 'ミーム', false],
            'どこにでも出る言葉だけが同じ' => ['今日の予定が多い', '今日の予定', false],
        ];
    }

    /** 重なるはずの組は1つ以上重なり、重ならないはずの組は 0 になることを確かめる */
    #[DataProvider('pairs')]
    public function test_overlap(string $source, string $text, bool $expected): void
    {
        $score = WordOverlap::score(WordOverlap::grams($source), $text);

        $this->assertSame($expected, $score > 0, "{$source} と {$text}");
    }

    /** いくつかの文からまとめてかけらを取り出せ、重なりの数が多いほど点が高くなることを確かめる */
    public function test_more_shared_pieces_score_higher(): void
    {
        $grams = WordOverlap::grams('体操着を忘れる', '玄関に体操着の袋がなかった');

        $this->assertGreaterThan(
            WordOverlap::score($grams, '玄関の掃除'),
            WordOverlap::score($grams, '玄関で体操着を渡す'),
        );
    }

    /** 文が空・ひらがなだけなら、かけらは出ない（比べようがない）ことを確かめる */
    public function test_no_pieces_from_kana_only_text(): void
    {
        $this->assertSame([], WordOverlap::grams('', 'なぜかな'));
    }
}
