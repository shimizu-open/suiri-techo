<?php

namespace Tests\Feature\Cases;

use App\Models\User;
use App\Queries\Cases\NewCaseDraft;
use App\Support\Guide\GuideLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestView;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/**
 * 事件を作る画面の1問ずつ進む形（views/cases/create-steps）：4つの問いの順・送る入力の名前・きっかけ・練習メニュー・
 * 相棒モード・仕事の場面の注意・子ども向けの言葉。受付が今の作る画面と同じ draft（NewCaseDraft）を渡す形で描いて確かめる
 */
class CreateStepsTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** その人の draft で、1問ずつ進む作る画面を描く。extra を渡すと draft の値を差し替える（子ども向けの印を切り替えるときなど） */
    private function render(User $user, array $query = [], array $extra = []): TestView
    {
        $this->actingAs($user)->withViewErrors([]);
        $draft = array_replace(app(NewCaseDraft::class)->for($user, $query), $extra);

        return $this->view('cases.create-steps', ['draft' => $draft]);
    }

    /** 4つの問いが順に並び、送り先が cases.store で、入力の名前が今の作る画面と同じで、注意書きが出ることを確かめる */
    public function test_four_questions_in_one_form_with_the_same_names(): void
    {
        $w = config('suiri.case_new');

        $this->render($this->member())
            ->assertSeeInOrder([$w['mystery']['title'], $w['clue']['title'], $w['hypothesis']['title'], $w['confirm']['title'], $w['confirm']['submit']])
            ->assertSee('action="'.route('cases.store').'"', false)
            ->assertSee('data-stepper', false)
            ->assertSee('name="title"', false)->assertSee('name="scene"', false)
            ->assertSee('name="clue_kind"', false)->assertSee('name="clue_body"', false)
            ->assertSee('name="hypothesis_body"', false)
            ->assertSee('name="origin_kind" value=""', false)->assertSee('name="practice_key" value=""', false)
            ->assertSee(config('suiri.notes.privacy'))
            ->assertSee(config('suiri.suggest.clue_questions.default.0.label'))
            ->assertDontSee('name="partner_mode"', false)
            ->assertDontSee('演繹');
    }

    /** 予定の「気になる」から来たら、きっかけを小さく出し、隠し項目で送ることを確かめる */
    public function test_origin_from_an_event_is_shown_and_sent(): void
    {
        $this->render($this->member(), ['origin' => 'event', 'label' => '歯医者（10:00）'])
            ->assertSee('きっかけ：予定「歯医者（10:00）」', false)
            ->assertSee('name="origin_kind" value="event"', false);
    }

    /** 練習メニューから来たら、なぞにメニューの問いかけが入り、手がかり・見立ての問いの例がメニューの問いかけになることを確かめる */
    public function test_practice_prompts_become_the_examples(): void
    {
        $practice = app(GuideLibrary::class)->practice('silent-dog');

        $this->render($this->member(), ['origin' => 'practice', 'practice' => 'silent-dog'])
            ->assertSee($practice['name'])
            ->assertSee('value="'.e($practice['prompts']['title']).'"', false)
            ->assertSee($practice['prompts']['clue'])->assertSee($practice['prompts']['hypothesis'])
            ->assertSee('name="practice_key" value="silent-dog"', false)
            ->assertDontSee(config('suiri.case_new.clue.example'));
    }

    /** 相棒の名前がある人だけ「相棒といっしょに考える？」が出て、入れたときの説明（事件を作ったあとに書く）も用意されることを確かめる */
    public function test_partner_toggle_only_for_people_with_a_partner(): void
    {
        $this->render($this->member(['partner_name' => '長男']))
            ->assertSee('name="partner_mode"', false)
            ->assertSee('長男といっしょに考える？')
            ->assertSee(__(config('suiri.case_new.hypothesis.partner'), ['partner' => '長男']));
    }

    /** 仕事の場面の事件がまだない人には約束の全文が、2回目からはやさしい一言だけが用意されることを確かめる */
    public function test_work_note_is_full_only_the_first_time(): void
    {
        $user = $this->member();
        $this->render($user)
            ->assertSee(config('suiri.case_new.scene.work_line'))
            ->assertSee(config('suiri.cases.work_caution.body'));

        $this->caseOf($user, ['scene' => 'work']);
        $this->render($user)
            ->assertSee(config('suiri.case_new.scene.work_line'))
            ->assertDontSee(config('suiri.cases.work_caution.body'));
    }

    /**
     * 子ども向けの印があれば、場面・手がかりの種類・見立ての呼び名が子ども向けになり、ないときは大人向けになることを確かめる。
     * ふだんの場面が親子の人には、draft（NewCaseDraft）が何も頼まなくても子ども向けの印を付けることも確かめる
     */
    public function test_kid_words_follow_the_kid_flag(): void
    {
        $user = $this->member(['default_scene' => 'family']);

        $this->render($user)->assertSee('目でたしかめた');

        $this->render($user, [], ['kid' => true])
            ->assertSee('目でたしかめた')->assertSee('ふだん')
            ->assertSee(__(config('suiri.case_new.hypothesis.meaning'), ['word' => '「かも」']), false);

        $this->render($user, [], ['kid' => false])
            ->assertDontSee('目でたしかめた')
            ->assertSee(__(config('suiri.case_new.hypothesis.meaning'), ['word' => '見立て']), false);
    }

    /** 検証のエラーで戻ってきたら、その欄にエラーの印（data-error）が付き、1問ずつの動きがその段から見せられることを確かめる */
    public function test_errors_are_marked_for_the_stepper(): void
    {
        $this->actingAs($this->member())->withViewErrors(['clue_body' => '最初の手がかりは500文字以下にしてください。']);
        $draft = app(NewCaseDraft::class)->for(auth()->user(), []);

        $this->view('cases.create-steps', ['draft' => $draft])
            ->assertSee('data-error', false)
            ->assertSee('最初の手がかりは500文字以下にしてください。');
    }
}
