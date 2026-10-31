<?php

namespace Tests\Unit\Enums;

use App\Enums\ClueKind;
use App\Enums\HypothesisStatus;
use App\Enums\Scene;
use App\Support\Label;
use Tests\TestCase;

/** 列挙と画面の言葉（config/suiri.php の labels）。場面ごとの出し分けと、子ども向けの言い方 */
class LabelTest extends TestCase
{
    /** 手がかりの種類は、共通・仕事の場面・子ども向けで言葉が変わり、出し分けのないものは共通の言葉になることを確かめる */
    public function test_clue_kind_labels_change_by_scene(): void
    {
        $this->assertSame('見た', ClueKind::Seen->label());
        $this->assertSame('見た・触れた・測った', ClueKind::Seen->label(Scene::Work));
        $this->assertSame('ご本人の言葉', ClueKind::Heard->label(Scene::Work));
        $this->assertSame('なかった', ClueKind::Absent->label(Scene::Work));
        $this->assertSame('見た', ClueKind::Seen->label(Scene::Family));
        $this->assertSame('目でたしかめた', ClueKind::Seen->kidLabel());
        $this->assertSame('ちがった', HypothesisStatus::Eliminated->kidLabel());
    }

    /** 列挙ではない言葉も、仕事の場面と子ども向けで出し分けられることを確かめる */
    public function test_words_change_by_scene(): void
    {
        $this->assertSame('もしそうなら', Label::word('prediction'));
        $this->assertSame('次回、確かめること', Label::word('prediction', Scene::Work));
        $this->assertSame('見立て（診断ではありません）', Label::word('hypothesis', Scene::Work));
        $this->assertSame('「かも」', Label::kidWord('hypothesis'));
        $this->assertSame('事件', Label::kidWord('case'));
    }

    /** すべての列挙のすべての値に、共通の言葉が決まっている（値そのものが出ない）ことを確かめる */
    public function test_every_enum_value_has_a_label(): void
    {
        // app/Enums の列挙を1つずつ取り出し、値ごとの言葉を確かめる
        foreach (glob(app_path('Enums/*.php')) as $file) {
            $enum = 'App\\Enums\\'.basename($file, '.php');
            // 列挙の値を1つずつ取り出し、言葉が値そのものでないことを確かめる
            foreach ($enum::cases() as $case) {
                $this->assertNotSame($case->value, $case->label(), "{$enum}::{$case->name}");
            }
        }
        $this->assertSame(['daily' => '日常', 'family' => '親子', 'work' => '仕事'], Scene::options());
    }

    /** 確かさの選択肢は 50〜95 の5刻みで10個あり、どれにも子ども向けの言い方があることを確かめる */
    public function test_confidence_choices(): void
    {
        $choices = config('suiri.confidence.choices');

        $this->assertSame(range(50, 95, 5), array_keys($choices));
        $this->assertNotContains(null, array_column($choices, 'kid'));
    }
}
