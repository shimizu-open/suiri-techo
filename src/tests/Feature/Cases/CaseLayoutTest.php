<?php

namespace Tests\Feature\Cases;

use App\Enums\HypothesisStatus;
use App\Models\Clue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 事件の画面と答え合わせの画面の置き方：使い終えた欄をたたみ、主なボタンをスクロールせずに押せるようにする */
class CaseLayoutTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 手がかりがあれば手がかりの欄は出さず（いまやることは次の段）、「ほかにできること」から来たとき（?open=add-clue・前からの ?open=clue）は手がかりのカードにすることを確かめる */
    public function test_clue_form_is_folded_when_clues_exist(): void
    {
        [$user, $case] = $this->board();
        $form = 'action="'.route('clues.store', $case).'"';

        $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSee('open=add-clue', false)->assertDontSee($form, false);
        // 「ほかにできること」の行からも、前からの住所からも、手がかりのカードになる
        foreach (['add-clue', 'clue'] as $open) {
            $this->get(route('cases.show', ['case' => $case, 'open' => $open]))->assertSee('見たことを、もう1つ教えて')->assertSee($form, false);
        }
    }

    /** 手がかりがまだない事件では、手がかりのカードを初めから出すことを確かめる */
    public function test_clue_form_is_open_for_a_new_case(): void
    {
        $user = $this->member();
        $case = $this->caseOf($user);

        $this->actingAs($user)->get(route('cases.show', $case))
            ->assertSee('見たことを1つ教えて')->assertSee('action="'.route('clues.store', $case).'"', false);
    }

    /** 生きている見立ては ○× のカードで聞き、消えた見立ては ○× を聞かずに（?open=fit- で来ても）要約に1行で残すことを確かめる */
    public function test_fits_of_an_eliminated_hypothesis_are_folded(): void
    {
        [$user, $case, , $hypothesis] = $this->board();
        Clue::factory()->create(['case_file_id' => $case->id]);

        $this->actingAs($user)->get(route('cases.show', $case))->assertSee('この見立てに合う？')
            ->assertSee('action="'.route('fits.update', [$hypothesis, $case->clues()->first()]).'"', false);

        $hypothesis->update(['status' => HypothesisStatus::Eliminated]);
        $this->get(route('cases.show', ['case' => $case, 'open' => 'fit-'.$hypothesis->id]))->assertDontSee('この見立てに合う？')
            ->assertSee(HypothesisStatus::Eliminated->label().'：'.$hypothesis->body);
    }

    /** 答え合わせ待ちの画面では、主なボタン（結果を保存する）は欄の中の1つだけで、上の帯に同じボタンを重ねないことを確かめる */
    public function test_resolve_page_has_one_save_button_in_the_form(): void
    {
        [$user, , , , $prediction] = $this->board(withPrediction: true);

        $this->actingAs($user)->get(route('predictions.show', $prediction))->assertOk()
            ->assertSeeInOrder(['id="resolve-form"', config('suiri.resolve.question'), config('suiri.resolve.save')], false)
            ->assertDontSee('form="resolve-form"', false);
    }
}
