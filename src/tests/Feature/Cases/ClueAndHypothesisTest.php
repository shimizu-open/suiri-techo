<?php

namespace Tests\Feature\Cases;

use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use App\Models\HypothesisStatusLog;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 手がかり（C-2）・見立て（C-3・C-5）・○×（C-4）を足す・直す・消す */
class ClueAndHypothesisTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 手がかりを足す・直す・消すことができ、本文が空なら断られることを確かめる */
    public function test_clue_crud(): void
    {
        [$user, $case, $clue] = $this->board();

        $this->actingAs($user)->post(route('clues.store', $case), ['kind' => 'heard', 'body' => '「体育はない」と言った', 'form_key' => 'add-clue'])
            ->assertRedirect(route('cases.show', $case).'#clues');
        $this->assertSame(2, $case->clues()->count());
        $this->actingAs($user)->post(route('clues.store', $case), ['kind' => 'heard', 'body' => ''])->assertSessionHasErrors('body');

        $this->actingAs($user)->patch(route('clues.update', $clue), ['kind' => 'absent', 'body' => '袋がなかった'])->assertRedirect();
        $this->assertSame(['absent', '袋がなかった'], [$clue->refresh()->kind->value, $clue->body]);

        $this->actingAs($user)->delete(route('clues.destroy', $clue))->assertRedirect();
        $this->assertModelMissing($clue);
    }

    /** 手がかりを保存した直後に、ほかの人がわかる情報らしきものがあれば W-5 の警告が出る（保存は止めない）ことを確かめる */
    public function test_clue_with_personal_info_shows_an_alert(): void
    {
        [$user, $case] = $this->board();

        $response = $this->actingAs($user)->post(route('clues.store', $case), ['kind' => 'heard', 'body' => '090-1234-5678 に電話があった']);

        $response->assertRedirect(route('cases.show', $case))->assertSessionHas('watson_alert', 'personal_info');
        $this->assertSame(2, $case->clues()->count());
        $this->actingAs($user)->withSession(['watson_alert' => 'personal_info'])->get(route('cases.show', $case))
            ->assertSee(config('watson.rules.personal_info.alert'));
    }

    /** 見立てを1つだけのときは「ほかの説明は？」と聞き、足す・本文を直す・消すことができることを確かめる */
    public function test_hypothesis_crud_and_second_prompt(): void
    {
        [$user, $case, , $hypothesis] = $this->board();

        $this->actingAs($user)->get(route('cases.show', $case))->assertSee('ほかの説明は？');
        $this->actingAs($user)->post(route('hypotheses.store', $case), ['body' => '朝に急いでいる'])->assertRedirect();
        $this->assertSame(2, $case->hypotheses()->count());

        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), ['body' => '時間割を見ない'])->assertRedirect();
        $this->assertSame('時間割を見ない', $hypothesis->refresh()->body);
        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), [])->assertSessionHasErrors(['body', 'status']);
    }

    /** 見立ての状態を変えると「考えを変えた記録」が残り、同じ状態を選んでも記録は増えないことを確かめる */
    public function test_status_change_is_logged(): void
    {
        [$user, , , $hypothesis] = $this->board();

        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), ['status' => 'likely'])->assertRedirect();
        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), ['status' => 'likely']);
        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), ['status' => 'eliminated']);

        $logs = HypothesisStatusLog::orderBy('id')->get();
        $this->assertSame([['considering', 'likely'], ['likely', 'eliminated']], $logs->map(fn ($l) => [$l->from_status->value, $l->to_status->value])->all());
        $this->assertSame($user->id, $logs->first()->user_id);
    }

    /** ○× を付ける・付け直す・外すことができ、× の付いた見立てには「消す？」を勧めることを確かめる */
    public function test_fits_and_eliminate_suggestion(): void
    {
        [$user, $case, $clue, $hypothesis] = $this->board();

        $this->actingAs($user)->put(route('fits.update', [$hypothesis, $clue]), ['fit' => 'fits'])
            ->assertRedirect(route('cases.show', $case).'#hyp-'.$hypothesis->id);
        $this->actingAs($user)->put(route('fits.update', [$hypothesis, $clue]), ['fit' => 'contradicts']);
        $this->assertSame(1, ClueHypothesis::count());
        $this->assertSame('contradicts', ClueHypothesis::first()->fit->value);
        $this->actingAs($user)->get(route('cases.show', ['case' => $case, 'open' => 'fit-'.$hypothesis->id]))
            ->assertSee(config('suiri.case_board.cards.fit.eliminate'))->assertSee(config('suiri.case_board.cards.fit.eliminate_button'));

        $this->actingAs($user)->delete(route('fits.destroy', [$hypothesis, $clue]))->assertRedirect();
        $this->assertSame(0, ClueHypothesis::count());
        $this->actingAs($user)->put(route('fits.update', [$hypothesis, $clue]), ['fit' => 'maybe'])->assertSessionHasErrors('fit');
    }

    /** 別の事件の手がかりには、自分のものでも ○× を付けられないことを確かめる */
    public function test_fit_needs_the_same_case(): void
    {
        [$user, , , $hypothesis] = $this->board();
        $otherCase = $this->caseOf($user);
        $otherClue = Clue::factory()->create(['case_file_id' => $otherCase->id]);

        $this->actingAs($user)->put(route('fits.update', [$hypothesis, $otherClue]), ['fit' => 'fits'])->assertForbidden();
        $this->assertSame(0, ClueHypothesis::count());
    }

    /** 見立てを消すと、その予想と確かめるタスクも消えることを確かめる */
    public function test_deleting_a_hypothesis_removes_its_verify_task(): void
    {
        [$user, , , $hypothesis, $prediction] = $this->board(true);

        $this->actingAs($user)->delete(route('hypotheses.destroy', $hypothesis))->assertRedirect();

        $this->assertModelMissing($hypothesis);
        $this->assertModelMissing($prediction);
        $this->assertSame(0, Task::count());
        $this->assertSame(0, Hypothesis::count());
    }
}
