<?php

namespace Tests\Feature\Cases;

use App\Models\Clue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** ワトソンのひとこと（W-1〜W-6）の画面での出し方：一度に1つ・閉じたら出さない・保存は止めない・規則のキーは決まったものだけ */
class WatsonNoteTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 当てはまる規則が2つあっても、事件の画面には優先度の高い1つだけ（閉じるボタンも1つ）が出ることを確かめる */
    public function test_only_one_note_at_a_time(): void
    {
        [$user, $case] = $this->board();
        Clue::factory()->create(['case_file_id' => $case->id, 'kind' => 'seen', 'body' => 'たぶん寝坊した']);

        $page = $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSee(config('watson.rules.seen_guess.title'))->assertSee('「たぶん寝坊した」');
        $this->assertSame(1, substr_count($page->getContent(), '>'.config('suiri.case_board.watson.label').'<'));
        $this->assertSame(1, substr_count($page->getContent(), 'aria-label="'.config('suiri.case_board.watson.close').'"'));
        $page->assertDontSee(config('watson.rules.single_hypothesis.body'));
    }

    /** 閉じると記録が残り、その事件では同じひとことが出ず、次の規則のひとことが出ることを確かめる */
    public function test_dismissed_note_does_not_come_back(): void
    {
        [$user, $case] = $this->board();
        Clue::factory()->create(['case_file_id' => $case->id, 'kind' => 'seen', 'body' => 'たぶん寝坊した']);

        $this->actingAs($user)->post(route('hints.dismiss', [$case, 'seen_guess']))->assertRedirect(route('cases.show', $case));
        $this->actingAs($user)->post(route('hints.dismiss', [$case, 'seen_guess']));

        $this->assertSame(1, $case->dismissedHints()->count());
        $this->actingAs($user)->get(route('cases.show', $case))
            ->assertDontSee(config('watson.rules.seen_guess.title'))->assertSee(config('watson.rules.single_hypothesis.body'));
    }

    /** ひとことに当てはまる文でも保存は止まらない（推測の言葉の入った「見た」の手がかりも保存できる）ことを確かめる */
    public function test_notes_never_block_saving(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->post(route('clues.store', $case), ['kind' => 'seen', 'body' => '雨が降ったので遅れたと思う'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(2, $case->clues()->count());
    }

    /** config/watson.php にない規則のキーは閉じられない（住所として見つからない）ことを確かめる */
    public function test_unknown_rule_cannot_be_dismissed(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->post('/cases/'.$case->id.'/hints/whatever')->assertNotFound();
        $this->assertSame(0, $case->dismissedHints()->count());
    }

    /** 仕事の場面の「よくなった」には W-4 が出て、ワトソンのひとことに難しい言葉（演繹など）が出ないことを確かめる */
    public function test_work_better_note(): void
    {
        [$user, $case] = $this->board(true, ['scene' => 'work']);
        Clue::factory()->create(['case_file_id' => $case->id, 'kind' => 'heard', 'body' => '前回より楽になったと言った']);
        $case->dismissedHints()->create(['rule' => 'single_hypothesis']);

        $this->actingAs($user)->get(route('cases.show', $case))
            ->assertSee(config('watson.rules.work_better.title'))->assertSee('自然な経過')->assertDontSee('演繹');
    }
}
