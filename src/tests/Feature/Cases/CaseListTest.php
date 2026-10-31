<?php

namespace Tests\Feature\Cases;

use App\Actions\Cases\ResolvePrediction;
use App\Models\Clue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 事件の一覧（C-12）の見せ方：空のとき・段ごとの進み具合の一言・閉じた事件・場面でしぼる・子ども向けの言葉 */
class CaseListTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 事件がまだ1つもないときは、つまみを出さず、空の一言と「最初の事件を作ってみよう」のボタンだけを出すことを確かめる */
    public function test_a_new_user_sees_only_the_first_case_button(): void
    {
        $w = config('suiri.case_list');

        $this->actingAs($this->member())->get(route('cases.index'))->assertOk()
            ->assertSee($w['empty']['none']['title'])->assertSee($w['empty']['none']['action'])->assertSee(route('cases.create'), false)
            ->assertDontSee($w['create'])->assertDontSee($w['leads']['open']);
    }

    /** 1件ごとに事件の番号と、まだ済んでいない最初の段（次は「見たこと」「どうして？」）を出すことを確かめる */
    public function test_each_case_shows_its_number_and_next_step(): void
    {
        $user = $this->member();
        $blank = $this->caseOf($user, ['title' => 'なぞだけの事件']);
        $withClue = $this->caseOf($user, ['title' => '手がかりのある事件']);
        Clue::factory()->create(['case_file_id' => $withClue->id]);

        $this->actingAs($user)->get(route('cases.index'))->assertOk()
            ->assertSeeInOrder(['事件 No.'.$withClue->id, '手がかりのある事件', '次は「どうして？」'])
            ->assertSeeInOrder(['事件 No.'.$blank->id, 'なぞだけの事件', '次は「見たこと」'])
            ->assertDontSee(config('suiri.case_list.due'));
    }

    /** 確かめる日が来た予想があれば、ほかに済んでいない段があっても「次は『答え合わせ』」と赤い札を出すことを確かめる */
    public function test_a_due_check_comes_first(): void
    {
        [$user, , $clue] = $this->board(true);
        $clue->delete();

        $this->actingAs($user)->get(route('cases.index'))->assertOk()
            ->assertSee('次は「答え合わせ」')->assertDontSee('次は「見たこと」')->assertSee(config('suiri.case_list.due'));
    }

    /** 確かめる日がまだ先の予想だけなら赤い札を出さず「確かめる日を待っているよ」と出すことを確かめる */
    public function test_a_future_check_is_waiting_not_due(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        $prediction->forceFill(['check_on' => today()->addDays(3)->toDateString()])->save();

        $this->actingAs($user)->get(route('cases.index'))->assertOk()
            ->assertSee(config('suiri.case_list.waiting'))->assertDontSee(config('suiri.case_list.due'));
    }

    /** 答え合わせ済みの予想があれば、答え合わせの段まで済みにして「答え合わせまでできたよ」と出すことを確かめる */
    public function test_a_resolved_prediction_completes_the_steps(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        app(ResolvePrediction::class)->handle($prediction, ['result' => 'hit']);

        $this->actingAs($user)->get(route('cases.index'))->assertOk()
            ->assertSee(config('suiri.case_list.all_done'))->assertDontSee(config('suiri.case_list.waiting'))
            ->assertDontSee(config('suiri.case_list.due'));
    }

    /** 閉じた事件のつまみでは、閉じた日と、その状態の一言（おあずけ＝迷宮入り）を出すことを確かめる */
    public function test_closed_cases_show_the_closing_day(): void
    {
        $user = $this->member();
        $this->caseOf($user, ['title' => 'おあずけの事件', 'status' => 'cold', 'closed_at' => today()]);

        $this->actingAs($user)->get(route('cases.index', ['status' => 'cold']))->assertOk()
            ->assertSee(config('suiri.case_list.leads.cold'))->assertSee('おあずけの事件')
            ->assertSee(__(config('suiri.case_list.closed_on'), ['date' => today()->isoFormat('M月D日')]));
        $this->actingAs($user)->get(route('cases.index', ['status' => 'solved']))->assertOk()
            ->assertSee(config('suiri.case_list.empty.solved.title'))->assertSee(config('suiri.case_list.create'));
    }

    /** 場面でしぼっているときは、そのことと「しぼるのをやめる」を出し、合う事件がなければ場面の空の一言を出すことを確かめる */
    public function test_scene_filter_is_announced_and_can_be_cleared(): void
    {
        $user = $this->member();
        $this->caseOf($user, ['title' => '日常の事件']);
        $w = config('suiri.case_list');

        $this->actingAs($user)->get(route('cases.index', ['scene' => 'work']))->assertOk()
            ->assertSee(__($w['scene_now'], ['scene' => '仕事']))->assertSee($w['scene_clear'])
            ->assertSee($w['empty']['scene']['title'])->assertDontSee('日常の事件');
    }

    /** ふだんの場面が親子の人には、つまみを子ども向けの言葉（調べ中・わかった・おあずけ）で出すことを確かめる */
    public function test_family_users_see_kid_tabs(): void
    {
        $user = $this->member(['default_scene' => 'family']);
        $this->caseOf($user, ['title' => '親子のなぞ', 'scene' => 'family']);

        $this->actingAs($user)->get(route('cases.index'))->assertOk()
            ->assertSeeInOrder(['調べ中', 'わかった', 'おあずけ', '親子のなぞ'])->assertSee('おやこ');
    }
}
