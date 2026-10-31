<?php

namespace Tests\Feature\Today;

use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * きょうの画面（今日とタスクを1つにまとめた画面）の並びと言葉：
 * いまやることのカード → きょうの予定とやること（見出しのすぐ下に1行で書く欄、やり残しは上に赤い印）
 * → これから・いつか・済み のたたんだ枠（件数つき、?open= の枠だけ開く）→ 今日の練習・捜査中の事件
 */
class TodayMergedLayoutTest extends TestCase
{
    use RefreshDatabase;

    /** 期日を「今日から何日ずれた日」の 'Y-m-d' で返す（マイナスは前の日） */
    private function day(int $offset): string
    {
        return today()->addDays($offset)->toDateString();
    }

    /** 上から、カード → 見出し → 1行で書く欄 → やり残し → 今日のやること → これから・いつか・済み → 捜査中の事件 の順に並ぶことを確かめる */
    public function test_sections_are_in_the_agreed_order(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['title' => 'きのうの宿題', 'due_on' => $this->day(-1)]);
        Task::factory()->for($user)->create(['title' => 'きょうの買い物']);
        Task::factory()->for($user)->create(['title' => 'あしたの準備', 'due_on' => $this->day(1)]);
        Task::factory()->for($user)->someday()->create(['title' => 'いつか読む本']);
        Task::factory()->for($user)->done()->create(['title' => '済んだ洗濯', 'due_on' => $this->day(-1)]);
        $later = config('suiri.today.later');

        $this->actingAs($user)->get(route('today'))->assertOk()->assertSeeInOrder([
            config('suiri.today.record.title'),
            config('suiri.today.agenda.title'),
            config('suiri.tasks.quick.label'),
            __(config('suiri.today.agenda.overdue'), ['count' => 1]),
            'きのうの宿題',
            'きょうの買い物',
            $later['upcoming']['label'], 'あしたの準備',
            $later['someday']['label'], 'いつか読む本',
            $later['done']['label'], '済んだ洗濯',
            config('suiri.today.cases.title'),
        ]);
    }

    /** やり残しの行は、左にもとの期日を赤で出し、やり残しがなければ赤い一言も出さないことを確かめる */
    public function test_overdue_rows_carry_a_red_mark(): void
    {
        $user = User::factory()->create();
        $overdue = __(config('suiri.today.agenda.overdue'), ['count' => 1]);
        $this->actingAs($user)->get(route('today'))->assertOk()->assertDontSee($overdue);

        Task::factory()->for($user)->create(['title' => 'おとといの電話', 'due_on' => $this->day(-2)]);
        $date = today()->subDays(2)->isoFormat(config('suiri.today.agenda.overdue_date'));

        $this->actingAs($user)->get(route('today'))->assertOk()
            ->assertSeeInOrder([$overdue, 'text-answer">'.$date.'</span>', 'おとといの電話'], false);
    }

    /** 1行で書く欄：題名の欄と、今日・明日・いつか の3つの送信ボタン（値は今日の日付・明日の日付・空）と「くわしく」があり、隠した期日の欄はないことを確かめる */
    public function test_quick_add_has_three_submit_buttons(): void
    {
        $quick = config('suiri.tasks.quick');
        $response = $this->actingAs(User::factory()->create())->get(route('today'))->assertOk()
            ->assertSee('action="'.route('tasks.store').'"', false)
            ->assertDontSee('name="back"', false)
            ->assertSee('placeholder="'.$quick['placeholder'].'"', false)
            ->assertSeeInOrder([
                config('suiri.today.agenda.title'), 'id="quick-title"',
                'name="due_on" value="'.$this->day(0).'"', $quick['today'],
                'name="due_on" value="'.$this->day(1).'"', $quick['tomorrow'],
                'name="due_on" value=""', $quick['someday'],
                'formaction="'.route('tasks.draft').'"', $quick['more'],
            ], false)
            ->assertDontSee('type="hidden" name="due_on"', false);

        $this->assertSame(3, substr_count($response->getContent(), 'name="due_on"'));
    }

    /** 3つのボタンの値のとおりに作れる：「いつか」（空）なら期日なし、「明日」なら明日の日付で作り、きょうの画面へ戻ることを確かめる（開く枠は受付が決める） */
    public function test_each_button_value_sets_the_due_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tasks.store'), ['title' => 'いつか行く店', 'due_on' => ''])
            ->assertRedirectContains(route('today'));
        $this->actingAs($user)->post(route('tasks.store'), ['title' => '明日出す手紙', 'due_on' => $this->day(1)])
            ->assertRedirectContains(route('today'));

        $this->assertNull($user->tasks()->where('title', 'いつか行く店')->value('due_on'));
        $this->assertSame($this->day(1), $user->tasks()->where('title', '明日出す手紙')->value('due_on'));
    }

    /** たたんだ枠は件数つきで、?open= の値の枠だけが開き、指定がない・知らない値ならどれも開かないことを確かめる */
    public function test_only_the_requested_fold_is_open(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->count(2)->create(['due_on' => $this->day(3)]);
        Task::factory()->for($user)->someday()->create();
        $later = config('suiri.today.later');
        $count = fn (int $n) => __($later['count'], ['count' => $n]);
        $isOpen = fn (string $html, string $name) => (bool) preg_match('/<details id="later-'.$name.'"[^>]*\sopen[\s>]/', $html);

        $html = $this->actingAs($user)->get(route('today', ['open' => 'someday']))->assertOk()
            ->assertSeeInOrder([$later['upcoming']['label'], $count(2), $later['someday']['label'], $count(1), $later['done']['label'], $count(0)])
            ->getContent();
        $this->assertTrue($isOpen($html, 'someday'));
        $this->assertFalse($isOpen($html, 'upcoming'));
        $this->assertFalse($isOpen($html, 'done'));

        // 指定がないとき・知らない値のときは、3つともたたんだまま
        foreach ([[], ['open' => 'today'], ['open' => 'nanika']] as $query) {
            $html = $this->actingAs($user)->get(route('today', $query))->assertOk()->getContent();
            $this->assertSame([false, false, false], [$isOpen($html, 'upcoming'), $isOpen($html, 'someday'), $isOpen($html, 'done')]);
        }
    }

    /** これからの枠では、日付ごとの見出しの下にやることが並び、明日の日には「明日」が付くことを確かめる */
    public function test_upcoming_is_grouped_by_date(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['title' => '3日後の用事', 'due_on' => $this->day(3)]);
        Task::factory()->for($user)->create(['title' => '明日の用事', 'due_on' => $this->day(1)]);
        $format = config('suiri.today.date_format');

        $this->actingAs($user)->get(route('today', ['open' => 'upcoming']))->assertOk()->assertSeeInOrder([
            config('suiri.today.later.tomorrow').'・'.today()->addDay()->isoFormat($format), '明日の用事',
            today()->addDays(3)->isoFormat($format), '3日後の用事',
        ]);
    }

    /** ふつうのやることの行：丸を押すと済み、本文を押すと開いて「直す」「気になる」「消す」が出ることを確かめる */
    public function test_a_task_row_opens_with_edit_curious_and_delete(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['title' => '牛乳を買う']);
        $row = config('suiri.tasks.row');

        $this->actingAs($user)->get(route('today'))->assertOk()
            ->assertSee('action="'.route('tasks.done', $task).'"', false)
            ->assertSeeInOrder([
                '<details class="group/row', '牛乳を買う', '</summary>',
                route('tasks.edit', $task), $row['edit'],
                'name="label" value="牛乳を買う"', config('suiri.today.curious'),
                $row['delete'], 'action="'.route('tasks.destroy', $task).'"',
            ], false);
    }

    /**
     * 答え合わせ待ちの予想を確かめるタスクは、行に「答え合わせ」が出て、開いても「消す」「気になる」は出ないことを確かめる。
     * いちばん上の答え合わせのカードに出ている予想の確かめるタスクは、やり残しにも二重に出さない
     */
    public function test_verify_task_rows_keep_the_answer_button(): void
    {
        $later = Prediction::factory()->create(['statement' => '来週は忘れないはず', 'check_on' => $this->day(2)]);
        $verify = Task::factory()->verify($later)->create();
        $due = Prediction::factory()->create(['user_id' => $later->user_id, 'hypothesis_id' => $later->hypothesis_id, 'statement' => 'きのう確かめるはずの予想', 'check_on' => $this->day(-1)]);
        Task::factory()->verify($due)->create();

        $html = $this->actingAs(User::find($later->user_id))->get(route('today', ['open' => 'upcoming']))->assertOk()
            ->assertSee(route('predictions.show', $later), false)->assertSee(config('suiri.tasks.words.resolve'))
            ->assertSee(route('tasks.edit', $verify), false)
            ->assertDontSee('action="'.route('tasks.destroy', $verify).'"', false)
            ->assertDontSee('name="label" value="確かめる：来週は忘れないはず"', false)
            ->assertDontSee(__(config('suiri.today.agenda.overdue'), ['count' => 1]))
            ->getContent();

        $this->assertSame(1, substr_count($html, 'きのう確かめるはずの予想'));
    }

    /** ほかの人のやることは、きょうの並び・やり残し・これから・いつか・済み のどこにも出ないことを確かめる */
    public function test_other_peoples_tasks_never_show(): void
    {
        $other = User::factory()->create();
        Task::factory()->for($other)->create(['title' => '人のきょう']);
        Task::factory()->for($other)->create(['title' => '人のやり残し', 'due_on' => $this->day(-1)]);
        Task::factory()->for($other)->create(['title' => '人のこれから', 'due_on' => $this->day(1)]);
        Task::factory()->for($other)->someday()->create(['title' => '人のいつか']);
        Task::factory()->for($other)->done()->create(['title' => '人の済み']);

        $response = $this->actingAs(User::factory()->create())->get(route('today', ['open' => 'done']))->assertOk();
        // ほかの人のやることの題名を1つずつ取り出し、画面に出ていないことを確かめる
        foreach (['人のきょう', '人のやり残し', '人のこれから', '人のいつか', '人の済み'] as $title) {
            $response->assertDontSee($title);
        }
    }
}
