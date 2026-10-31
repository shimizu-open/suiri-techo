<?php

namespace Tests\Feature\Cases;

use App\Enums\CaseStatus;
use App\Enums\Scene;
use App\Models\Hypothesis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 決まった型の提案（S-1 手がかりの問いかけ・S-2 別の説明の型・S-3 予想の穴埋め）が、場面・見立ての数・親子の人で出し分けられること */
class StarterSuggestionsTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** ふだんの場面では「いつ？」などの問いかけが出て、「無かったもの」は種類を「なかった」に切り替えることを確かめる */
    public function test_clue_questions_for_daily_cases(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->get(route('cases.show', ['case' => $case, 'open' => 'add-clue']))->assertOk()
            ->assertSee('data-suggest-target="f-step-clue"', false)
            ->assertSeeInOrder(['いつ？', '何回？', 'どこで？', 'だれが？', 'いつもと違ったことは？', 'あるはずなのに無かったものは？'])
            ->assertSee('data-text="あるはずなのに、なかったもの：" data-kind="absent"', false)
            ->assertDontSee('どのくらい？（数で）');
    }

    /** 仕事の場面では「どこが？」「どのくらい？（数で）」などに替わり、「ご本人はどう言った？」は種類を「ご本人の言葉」に切り替えることを確かめる */
    public function test_clue_questions_for_work_cases(): void
    {
        [$user, $case] = $this->board(caseAttributes: ['scene' => Scene::Work]);

        $this->actingAs($user)->get(route('cases.show', ['case' => $case, 'open' => 'add-clue']))->assertOk()
            ->assertSeeInOrder(['どこが？', 'どのくらい？（数で）', '前回と比べて？', 'ご本人はどう言った？'])
            ->assertSee('data-kind="heard"', false)
            ->assertDontSee('だれが？');
    }

    /** 別の説明の型は、主なボタンが最初の画面に入るよう1行にたたみ、見立てが0〜1個の間だけ「押すと書き出しが入る」の見出しで目立たせることを確かめる */
    public function test_hypothesis_types_are_emphasized_only_with_few_hypotheses(): void
    {
        [$user, $case] = $this->board();
        $url = route('cases.show', ['case' => $case, 'open' => 'add-hypothesis']);

        $this->actingAs($user)->get($url)
            ->assertSee('data-suggest-target="f-step-hypothesis"', false)
            ->assertSee(config('suiri.suggest.captions.hypothesis'))
            ->assertSeeInOrder(['時間', '時期・曜日・時刻が関係？', '習慣', '環境', '人', '偶然', '数え方', '逆向き', '原因と結果が逆では？']);

        Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => '体育のある日だけ忘れる']);

        $this->get($url)->assertSee(config('suiri.suggest.captions.hypothesis_folded'))
            ->assertDontSee(config('suiri.suggest.captions.hypothesis'));
    }

    /** ふだんの場面が親子の人には子ども向けの言い方、仕事の事件には W-4 と同じ5つの型が出ることを確かめる */
    public function test_hypothesis_types_for_kids_and_work(): void
    {
        [$user, $case] = $this->board();
        $user->update(['default_scene' => Scene::Family]);
        $url = route('cases.show', ['case' => $case, 'open' => 'add-hypothesis']);

        $this->actingAs($user)->get($url)
            ->assertSee('日や時間が関係ある？')->assertSee('data-text="じつは逆で、"', false)
            ->assertDontSee('時期・曜日・時刻が関係？');

        $case->update(['scene' => Scene::Work]);

        $this->get($url)
            ->assertSeeInOrder(['自然な経過', '生活の変化', 'ほかの治療', '測り方のばらつき', '期待'])
            ->assertDontSee('日や時間が関係ある？');
    }

    /** 予想の欄には、見立ての本文を入れた穴埋めの文が入る「型を使う」のボタン（data-text）が出ることを確かめる */
    public function test_prediction_template_uses_the_hypothesis(): void
    {
        [$user, $case] = $this->board();
        $template = 'もし『前の晩に時間割を見ていない』が正しいなら、＿＿のときは＿＿のはず';

        $this->actingAs($user)->get(route('cases.show', $case))
            ->assertSee('data-text="'.$template.'"', false)
            ->assertSee(config('suiri.case_board.cards.predict.template'));

        $case->update(['scene' => Scene::Work]);
        $this->get(route('cases.show', $case))->assertSee('が正しいなら、次回＿＿のときは＿＿のはず');
    }

    /** 穴埋めの印（＿＿）が残ったままの予想は保存できず、書き換えを頼む文が出ることを確かめる（保存後は直せないため） */
    public function test_prediction_with_blanks_is_not_saved(): void
    {
        [$user, , , $hypothesis] = $this->board();

        $this->actingAs($user)->post(route('predictions.store', $hypothesis), [
            'form_key' => 'predict-'.$hypothesis->id,
            'statement' => 'もし『前の晩に時間割を見ていない』が正しいなら、＿＿のときは＿＿のはず',
            'confidence' => 70,
            'check_on' => today()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['statement' => trans('validation.custom.statement.not_regex')]);

        $this->assertSame(0, $hypothesis->predictions()->count());
    }

    /**
     * 提案のボタンの並び（書き出し・確かめる日の候補）は、初めは隠して（hidden）出し、JavaScript が見せる印（data-suggest-chips）を持つことを確かめる。
     * JavaScript が動かないときに、押しても何も起きないボタンを見せないため。日付の欄はそのまま見えて、手で選べる
     */
    public function test_chip_rows_start_hidden_until_javascript_shows_them(): void
    {
        [$user, $case] = $this->board();

        $html = $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSee('<div hidden data-suggest-chips class="grid grid-cols-3 gap-2">', false)
            ->assertSee('<input id="f-step-check-on" type="date" name="check_on"', false)
            ->getContent();
        $this->assertMatchesRegularExpression('#<details hidden data-suggest-chips\s+open\s+data-suggest-target="f-step-statement"#', $html);

        // 提案のボタン（型を使う・明日・3日後・1週間後など）は、どれも隠した並び（data-suggest-chips）の中にだけある
        $button = '<button type="button" data-suggest ';
        $this->assertGreaterThanOrEqual(4, substr_count($html, $button));
        $this->assertSame(substr_count($html, $button), substr_count(implode('', $this->chipRows($html)), $button));
    }

    /**
     * HTML から、隠した提案の並び（data-suggest-chips の付いた要素）の中身を取り出す
     *
     * @return list<string>
     */
    private function chipRows(string $html): array
    {
        preg_match_all('#<(details|div) hidden data-suggest-chips.*?</\1>#s', $html, $rows);

        return $rows[0];
    }

    /** 閉じた事件には足す欄がないので、提案のボタンも出ないことを確かめる */
    public function test_closed_cases_have_no_suggestions(): void
    {
        [$user, $case] = $this->board(caseAttributes: ['status' => CaseStatus::Solved, 'closed_at' => now()]);

        $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertDontSee('data-suggest', false);
    }
}
