<?php

namespace Tests\Unit\Watson;

use App\Services\Watson\TextSigns;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** ワトソンのひとことが文の中から探す「しるし」。当たるべき文に当たり、まぎらわしい文には当たらない（誤検知しにくい）こと */
class TextSignsTest extends TestCase
{
    /** 推測の言葉が入った文と、入っていないのにまぎらわしい文 */
    public static function guessTexts(): array
    {
        return [
            'と思う' => ['寝坊したと思う', true],
            'だろう' => ['雨だったのだろう', true],
            'らしい' => ['遅れているらしい', true],
            'みたい' => ['疲れているみたいだった', true],
            'ようだ' => ['急いでいるようだった', true],
            'たぶん' => ['たぶん忘れた', true],
            'おそらく' => ['おそらく前の晩に用意していない', true],
            'はず' => ['袋は玄関にあるはずだ', true],
            '気がする' => ['いつもより暗い気がする', true],
            'かもしれない' => ['風のせいかもしれない', true],
            '新しい（らしいの誤検知）' => ['あたらしい靴をはいていた', false],
            '素晴らしい' => ['素晴らしい天気だった', false],
            'やってみたい（みたいの誤検知）' => ['弟は「やってみたい」と言った', false],
            'はずれ（はずの誤検知）' => ['ボタンがはずれていた', false],
            'ただの事実' => ['玄関に体操着の袋がなかった', false],
        ];
    }

    /** 「見た」の手がかりの推測の言葉（W-1）が、当たるべき文だけに当たることを確かめる */
    #[DataProvider('guessTexts')]
    public function test_guess_words(string $text, bool $expected): void
    {
        $this->assertSame($expected, app(TextSigns::class)->hasGuess($text));
    }

    /** 原因を決める言葉が入った文と、場所や時刻の「から」などのまぎらわしい文 */
    public static function causeTexts(): array
    {
        return [
            'ので' => ['雨が降ったので遅れた', true],
            'だから' => ['月曜だから混んでいた', true],
            '形容詞＋から' => ['寒いから窓が閉まっていた', true],
            'ため' => ['工事のため、道が閉じていた', true],
            'せいで' => ['風のせいで倒れていた', true],
            'おかげで' => ['日差しのおかげで乾いていた', true],
            'によって' => ['猫によって倒された', true],
            '場所のから' => ['駅から歩いてきた', false],
            '時刻のから' => ['7時から雨が降っていた', false],
            'てから' => ['家に帰ってから靴を脱いだ', false],
            'あしたから' => ['あしたから工事と書いてあった', false],
            'この電車' => ['このでんしゃは8両だった', false],
            'ため息' => ['ため息をついていた', false],
        ];
    }

    /** 「見た」の手がかりの原因の言葉（W-2）が、当たるべき文だけに当たることを確かめる */
    #[DataProvider('causeTexts')]
    public function test_cause_words(string $text, bool $expected): void
    {
        $this->assertSame($expected, app(TextSigns::class)->hasCause($text));
    }

    /** 仕事の場面の「よくなった・楽になった・改善」（W-4）に当たり、ふつうの文には当たらないことを確かめる */
    public function test_feels_better_words(): void
    {
        $signs = app(TextSigns::class);

        $this->assertTrue($signs->hasFeelsBetter('前回より肩がよくなったと言った'));
        $this->assertTrue($signs->hasFeelsBetter('楽になりました、とのこと'));
        $this->assertTrue($signs->hasFeelsBetter('首の動きが改善'));
        $this->assertFalse($signs->hasFeelsBetter('右肩が上がって見えた'));
        $this->assertFalse($signs->hasFeelsBetter('よく眠れたと言った'));
    }

    /** ほかの人がわかる情報らしきもの（電話番号・メール・名前）と、記号や呼び方などのまぎらわしい文 */
    public static function personalTexts(): array
    {
        return [
            '携帯の番号' => ['090-1234-5678 に電話', true],
            '固定の番号（かっこ）' => ['03(1234)5678', true],
            '全角の番号' => ['０９０１２３４５６７８', true],
            'メール' => ['taro@example.com に送った', true],
            '全角のメール' => ['ｔａｒｏ＠ｅｘａｍｐｌｅ．ｃｏｍ', true],
            '漢字の名前＋さん' => ['山田さんが遅れてきた', true],
            '漢字の名前＋様' => ['佐藤様は右肩を気にしていた', true],
            'カタカナの名前' => ['スミスさんは笑った', true],
            '記号の人' => ['同僚Aさんが遅れてきた', false],
            'お母さん' => ['お母さんが先に起きた', false],
            '店員さん' => ['店員さんが2人いた', false],
            '看護師さん' => ['看護師さんが通った', false],
            'お疲れ様' => ['お疲れ様と言った', false],
            '様子' => ['様子がいつもと違った', false],
            '日付' => ['2026-10-31 に見た', false],
            '体温と回数' => ['36.5度、3回、10:30', false],
        ];
    }

    /** ほかの人がわかる情報らしきもの（W-5）が、当たるべき文だけに当たることを確かめる */
    #[DataProvider('personalTexts')]
    public function test_personal_info(string $text, bool $expected): void
    {
        $this->assertSame($expected, app(TextSigns::class)->hasPersonalInfo($text));
    }
}
