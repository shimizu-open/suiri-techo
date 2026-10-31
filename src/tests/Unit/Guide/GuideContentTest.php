<?php

namespace Tests\Unit\Guide;

use App\Support\Guide\GuideLibrary;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** 教材のファイル（database/data/guide/*.json）の形：項目がそろっているか・キーが重ならないか・技能のキーが S1〜S10 にあるか */
class GuideContentTest extends TestCase
{
    /** 技能のキー（S1〜S10） */
    private const SKILL_KEYS = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10'];

    /** 練習メニューのキー（ほかの担当とのつなぎ目なので変えない） */
    private const PRACTICE_KEYS = ['seventeen-steps', 'sleeve-and-knee', 'post-office-note', 'temporary-label', 'first-rule',
        'silent-dog', 'check-the-knee', 'link-the-chain', 'attic-shelf', 'norbury-book', 'book-of-life'];

    /** 技能は S1〜S10 の10件で、どれにも説明・引用・3つの場面の練習の例・よくある失敗がそろっていることを確かめる */
    public function test_skills_have_every_field(): void
    {
        $skills = $this->library()->skills();
        $this->assertSame(self::SKILL_KEYS, array_column($skills, 'key'));

        // 技能を1つずつ、文の欄が空でないか確かめる
        foreach ($skills as $skill) {
            $this->assertFilledStrings($skill, ['name', 'kid_name', 'summary', 'body', 'kid_body', 'quote_en', 'quote_source', 'pitfall'], $skill['key']);
            $this->assertFilledStrings($skill['practice'], ['adult', 'kid', 'work'], $skill['key'].'.practice');
            $this->assertMatchesRegularExpression('/^『[^』]+』/u', $skill['quote_source'], $skill['key'].' の出典に作品名がない');
        }
    }

    /** 名推理カードはキーが重ならず、どれにも必要な欄がそろい、技能のキーが S1〜S10 にあり、外した場面も混ざっていることを確かめる */
    public function test_scenes_have_every_field(): void
    {
        $scenes = $this->library()->scenes();
        $keys = array_column($scenes, 'key');
        $this->assertSame(array_unique($keys), $keys, 'カードのキーが重なっている');
        $this->assertGreaterThanOrEqual(20, count($scenes));
        $this->assertGreaterThanOrEqual(3, count(array_filter(array_column($scenes, 'is_mistake'))), 'ホームズが外した場面が少ない');

        // カードを1枚ずつ、欄の形を確かめる
        foreach ($scenes as $scene) {
            $this->assertLessThanOrEqual(40, strlen($scene['key']), 'scene_attempts.scene_key は40文字まで');
            $this->assertFilledStrings($scene, ['title', 'story_ja', 'story_en', 'chapter', 'front', 'reasoning', 'verification', 'kid_hint'], $scene['key']);
            $this->assertTrue($scene['quote_en'] === null || filled($scene['quote_en']), $scene['key'].' の quote_en');
            $this->assertIsBool($scene['is_mistake']);
            $this->assertNotEmpty($scene['skills']);
            $this->assertEmpty(array_diff($scene['skills'], self::SKILL_KEYS), $scene['key'].' に知らない技能のキー');
        }
    }

    /** 名推理カードの表（front）に、ホームズの結論の文がそのまま書かれていないことを確かめる */
    public function test_scene_fronts_do_not_give_away_the_conclusion(): void
    {
        // カードを1枚ずつ、結論の最初の文が表に入っていないか確かめる
        foreach ($this->library()->scenes() as $scene) {
            $conclusion = trim(explode('。', explode('結論：', $scene['reasoning'])[1] ?? '')[0]);
            $this->assertNotSame('', $conclusion, $scene['key'].' の推理に「結論：」がない');
            $this->assertStringNotContainsString($conclusion, $scene['front'], $scene['key'].' の表に結論が書かれている');
        }
    }

    /** 練習メニューは決まった11種で、技能のキーが S1〜S10 にあり、問いかけ（prompts）は4つのキーがそろっていることを確かめる */
    public function test_practices_have_every_field(): void
    {
        $practices = $this->library()->practices();
        $this->assertSame(self::PRACTICE_KEYS, array_column($practices, 'key'));

        // 練習メニューを1つずつ、欄の形を確かめる
        foreach ($practices as $practice) {
            $this->assertFilledStrings($practice, ['name', 'skill', 'origin', 'description', 'kid_description'], $practice['key']);
            $this->assertContains($practice['skill'], self::SKILL_KEYS);
            $this->assertEqualsCanonicalizing(['title', 'clue', 'hypothesis', 'prediction'], array_keys($practice['prompts']));
            $this->assertFilledStrings($practice['prompts'], ['title', 'clue', 'hypothesis', 'prediction'], $practice['key'].'.prompts');
            // 問いかけは毎日の画面に出るので、難しい言葉が入っていないか確かめる
            foreach ($practice['prompts'] as $text) {
                $this->assertDoesNotMatchRegularExpression('/演繹|帰納|アブダクション/u', $text);
            }
        }
    }

    /** 演繹・帰納・アブダクションの説明は3つそろい、大人向け・子ども向け・原作の場面・アプリの段があることを確かめる */
    public function test_reasoning_has_three_kinds(): void
    {
        $reasoning = $this->library()->reasoning();
        $this->assertFilledStrings($reasoning, ['intro', 'kid_intro', 'note', 'kid_note'], 'reasoning');
        $this->assertEqualsCanonicalizing(['abduction', 'deduction', 'induction'], array_column($reasoning['items'], 'key'));

        // 3つを1つずつ、欄の形と、つないだカードがあるかを確かめる
        foreach ($reasoning['items'] as $item) {
            $this->assertFilledStrings($item, ['name', 'kid_name', 'step', 'adult', 'kid', 'certainty', 'kid_certainty', 'holmes_scene', 'in_app'], $item['key']);
            $this->assertNotNull($this->library()->scene($item['scene']), $item['key'].' のカードがない');
        }
    }

    /** 入口の流れの段が10の技能をすべて含み、つないだ技能・カード・推論・行き先がどれもあることを確かめる */
    public function test_overview_steps_point_to_existing_content(): void
    {
        $overview = $this->library()->overview();
        $reasoningKeys = array_column($this->library()->reasoning()['items'], 'key');
        $this->assertFilledStrings($overview, ['intro', 'flow_intro', 'mistake_note'], 'overview');
        $this->assertEqualsCanonicalizing(self::SKILL_KEYS, array_values(array_unique(array_merge(...array_column($overview['steps'], 'skills')))));

        // 流れの段を1つずつ、つないだ先があるか確かめる
        foreach ($overview['steps'] as $step) {
            $this->assertEmpty(array_diff($step['skills'], self::SKILL_KEYS), $step['key']);
            $this->assertTrue($step['scene'] === null || $this->library()->scene($step['scene']) !== null, $step['key'].' のカード');
            $this->assertTrue($step['reasoning'] === null || in_array($step['reasoning'], $reasoningKeys, true), $step['key'].' の推論');
        }
        // 入口の案内を1つずつ、行き先のルートがあるか確かめる
        foreach ($overview['entries'] as $entry) {
            $this->assertTrue(Route::has($entry['route']), $entry['route']);
        }
    }

    /** 教材のどこにも、効果を誇張する言い方（必ず分かる・身につく・力が伸びる など）がないことを確かめる */
    public function test_content_does_not_promise_effects(): void
    {
        // 教材のファイルを1つずつ読み、誇張の言い方を探す
        foreach (glob(database_path('data/guide/*.json')) as $path) {
            $this->assertDoesNotMatchRegularExpression('/必ず分か|身につ|力が伸び|頭が良く|上達する/u', file_get_contents($path), basename($path));
        }
    }

    /** 教材を読む係を返す */
    private function library(): GuideLibrary
    {
        return app(GuideLibrary::class);
    }

    /** 配列の中の指定した欄が、どれも空でない文字列かを確かめる */
    private function assertFilledStrings(array $item, array $fields, string $where): void
    {
        // 欄を1つずつ、文字列で空でないか確かめる
        foreach ($fields as $field) {
            $this->assertIsString($item[$field] ?? null, "{$where} の {$field} がない");
            $this->assertNotSame('', trim($item[$field]), "{$where} の {$field} が空");
        }
    }
}
