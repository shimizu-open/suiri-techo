<?php

namespace Tests\Feature\Cases;

use App\Models\CaseFile;
use App\Support\Guide\GuideLibrary;
use App\Support\Label;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 事件を作る（C-1・C-10）：きっかけ・練習メニュー・一番軽い記録・仕事の場面の注意・W-5 の警告 */
class CreateCaseTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 作る画面に、なぞ・場面・最初の手がかり（見たこと）・最初の見立ての欄と「事件を作る」が1つのフォームに順に並び、個人情報の注意書きが出ることを確かめる */
    public function test_create_page_has_the_lightest_form(): void
    {
        $w = config('suiri.case_new');

        $this->actingAs($this->member())->get(route('cases.create'))->assertOk()
            ->assertSeeInOrder([
                $w['mystery']['label'], 'name="title"', $w['scene']['legend'], 'name="scene"',
                $w['clue']['label'], 'name="clue_body"', Label::word('hypothesis'), 'name="hypothesis_body"', $w['confirm']['submit'],
            ], false)
            ->assertSee(config('suiri.notes.privacy'))
            ->assertDontSee('演繹');
    }

    /** 題名の写し（label）を URL で送っても受け取らず、画面にも隠し項目にも出ないことを確かめる（題名を URL に載せない決まり） */
    public function test_a_label_in_the_url_is_ignored(): void
    {
        $this->actingAs($this->member())->get(route('cases.create', ['origin' => 'task', 'label' => '歯医者 10:00']))
            ->assertOk()->assertDontSee('歯医者 10:00')
            ->assertSee('name="origin_label" value=""', false);
    }

    /** 予定・タスクの「気になる」（POST /cases/draft）は、題名を URL に載せずに作る画面へ持っていくことを確かめる */
    public function test_curious_draft_carries_the_label_without_the_url(): void
    {
        $this->actingAs($this->member())->post(route('cases.draft'), ['origin' => 'event', 'label' => '歯医者（10:00）'])
            ->assertRedirect(route('cases.create'));

        $this->get(route('cases.create'))->assertOk()
            ->assertSee('きっかけ：予定「歯医者（10:00）」', false)
            ->assertSee('name="origin_kind" value="event"', false);
    }

    /** 練習メニューから来たら、メニューの問いかけが各欄の案内になり、名前とキーが事件に残り、詳細の予想の欄にも問いかけが出ることを確かめる */
    public function test_practice_prompts_become_hints_and_are_saved(): void
    {
        $user = $this->member();
        $practice = app(GuideLibrary::class)->practice('silent-dog');

        $this->actingAs($user)->get(route('cases.create', ['origin' => 'practice', 'practice' => 'silent-dog']))->assertOk()
            ->assertSee($practice['name'])->assertSee($practice['prompts']['title'])
            ->assertSee($practice['prompts']['clue'])->assertSee($practice['prompts']['hypothesis']);

        $this->actingAs($user)->post(route('cases.store'), [
            'title' => $practice['prompts']['title'], 'scene' => 'daily',
            'origin_kind' => 'practice', 'origin_label' => $practice['name'], 'practice_key' => 'silent-dog',
            'hypothesis_body' => '犬は来た人を知っていた',
        ])->assertRedirect();

        $case = CaseFile::firstOrFail();
        $this->assertSame('silent-dog', $case->practice_key);
        $this->assertSame($practice['name'], $case->origin_label);
        $this->actingAs($user)->get(route('cases.show', $case))->assertSee($practice['prompts']['clue']);
        $this->get(route('cases.show', ['case' => $case, 'open' => 'predict-'.$case->hypotheses()->first()->id]))
            ->assertSee($practice['prompts']['prediction']);
    }

    /** 知らない練習メニューのキーは受け付けず、作る画面ではきっかけなしになることを確かめる */
    public function test_unknown_practice_is_ignored(): void
    {
        $user = $this->member();
        $this->actingAs($user)->get(route('cases.create', ['origin' => 'practice', 'practice' => 'nope']))
            ->assertOk()->assertSee('name="origin_kind" value=""', false);

        $this->actingAs($user)->post(route('cases.store'), ['title' => 'なぞ', 'scene' => 'daily', 'origin_kind' => 'practice', 'practice_key' => 'nope'])
            ->assertSessionHasErrors('practice_key');
    }

    /** なぞと手がかり1つと見立て1つだけで保存でき、持ち主が写り、事件の詳細へ進むことを確かめる */
    public function test_lightest_record_saves_with_one_clue_and_one_hypothesis(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->post(route('cases.store'), [
            'title' => '体操着をなぜ忘れる？', 'scene' => 'family',
            'clue_kind' => 'absent', 'clue_body' => '玄関に袋がなかった', 'hypothesis_body' => '前の晩に用意していない',
        ]);

        $case = $user->caseFiles()->firstOrFail();
        $response->assertRedirect(route('cases.show', $case));
        $this->assertSame('absent', $case->clues()->first()->kind->value);
        $this->assertSame($user->id, $case->clues()->first()->user_id);
        $this->assertSame($user->id, $case->hypotheses()->first()->user_id);
        $this->assertSame('open', $case->status->value);
    }

    /** なぞがないと保存できず、なぞだけでも保存できる（手がかりと見立ては任意）ことを確かめる */
    public function test_title_is_required_but_the_rest_is_optional(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post(route('cases.store'), ['title' => '  ', 'scene' => 'daily'])->assertSessionHasErrors('title');
        $this->actingAs($user)->post(route('cases.store'), ['title' => '朝の電車はなぜ混む？', 'scene' => 'daily'])->assertRedirect();

        $this->assertSame(0, $user->caseFiles()->first()->clues()->count());
    }

    /** 仕事の場面の事件がまだない人には全文の注意が、2回目からは短い注意が出ることを確かめる */
    public function test_work_caution_is_full_the_first_time(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('cases.create'))->assertSee(config('suiri.cases.work_caution.body'));

        $this->caseOf($user, ['scene' => 'work']);
        $this->actingAs($user)->get(route('cases.create'))
            ->assertDontSee(config('suiri.cases.work_caution.body'))->assertSee(config('suiri.notes.privacy_work'));
    }

    /** 保存した文にほかの人がわかる情報らしきものがあれば、保存はしたうえで W-5 の警告が出ることを確かめる */
    public function test_personal_info_warns_but_saves(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post(route('cases.store'), ['title' => '山田さんはなぜ遅れる？', 'scene' => 'daily'])
            ->assertSessionHas('watson_alert', 'personal_info');

        $case = $user->caseFiles()->firstOrFail();
        $this->actingAs($user)->withSession(['watson_alert' => 'personal_info'])->get(route('cases.show', $case))
            ->assertSee(config('watson.rules.personal_info.alert'));
    }
}
