<?php

namespace Tests\Feature\Cases;

use App\Models\Clue;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\Task;
use App\Queries\Cases\OpenCasesQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 事件の一覧と事件の画面（C-12）：絞り込み・1画面の並び・いまやることのカード・なぞと場面を直す・消す */
class CaseBoardTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 一覧は自分の事件だけを、状態と場面で絞って出し、作るボタンと答え合わせの日の札を出すことを確かめる */
    public function test_index_filters_by_status_and_scene(): void
    {
        [$user] = $this->board(true, ['title' => '捜査中の日常の事件']);
        $this->caseOf($user, ['title' => '親子の事件', 'scene' => 'family']);
        $this->caseOf($user, ['title' => '解決した事件', 'status' => 'solved', 'closed_at' => now()]);
        $this->caseOf($this->member(), ['title' => '他人の事件']);

        $this->actingAs($user)->get(route('cases.index'))->assertOk()
            ->assertSee(config('suiri.case_list.create'))->assertSee(route('cases.create'), false)
            ->assertSee('捜査中の日常の事件')->assertSee('親子の事件')->assertSee(config('suiri.case_list.due'))
            ->assertDontSee('解決した事件')->assertDontSee('他人の事件');
        $this->actingAs($user)->get(route('cases.index', ['scene' => 'family']))
            ->assertSee('親子の事件')->assertDontSee('捜査中の日常の事件');
        $this->actingAs($user)->get(route('cases.index', ['status' => 'solved']))
            ->assertSee('解決した事件')->assertDontSee('親子の事件');
    }

    /** 事件の画面は「なぞ → 済んだ段の要約（見たこと・どうして？・もしそうなら）→ いまやることのカード」の順に1画面に並ぶことを確かめる */
    public function test_show_puts_everything_on_one_page_in_order(): void
    {
        [$user, $case] = $this->board(true, ['title' => '体操着をなぜ忘れる？']);

        $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSeeInOrder(['体操着をなぜ忘れる？', '見たこと 1つ', '玄関に体操着の袋がなかった', 'どうして？ 1つ',
                '前の晩に時間割を見ていない', 'もしそうなら 1つ', '前の晩に時間割を見れば、袋が玄関にあるはず', '答え合わせをしよう']);
    }

    /** いまやることのカードが、手がかりがない → 見立てがない → 予想がない → 確かめる日が来た、で変わることを確かめる */
    public function test_next_step_follows_the_state_of_the_case(): void
    {
        $user = $this->member();
        $case = $this->caseOf($user);
        $show = fn () => $this->actingAs($user)->get(route('cases.show', $case));

        $show()->assertSee('見たことを1つ教えて');
        Clue::factory()->create(['case_file_id' => $case->id]);
        $show()->assertSee('どうしてだと思う？')->assertSee('見立て＝「どうしてだろう？」の答えの案', false);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id]);
        $show()->assertSee('もしそうなら、何が見える？')->assertSee('action="'.route('predictions.store', $hypothesis).'"', false);
        Prediction::factory()->due()->create(['hypothesis_id' => $hypothesis->id]);
        $show()->assertSee('答え合わせをしよう');
    }

    /** なぞと場面を直せることと、空のなぞは断られることを確かめる */
    public function test_update_title_and_scene(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->patch(route('cases.update', $case), ['title' => '新しいなぞ', 'scene' => 'work'])->assertRedirect();
        $this->assertSame('新しいなぞ', $case->refresh()->title);
        $this->assertSame('work', $case->scene->value);

        $this->actingAs($user)->patch(route('cases.update', $case), ['title' => '', 'scene' => 'work'])->assertSessionHasErrors('title');
    }

    /** 仕事の場面では、手がかりの種類と見立て・予想の言い方が施術者向けに変わり、注意が入力欄の上に出ることを確かめる */
    public function test_work_scene_changes_the_words(): void
    {
        [$user, $case, , $hypothesis] = $this->board(false, ['scene' => 'work']);
        $show = fn (string $open) => $this->actingAs($user)->get(route('cases.show', ['case' => $case, 'open' => $open]));

        $show('add-clue')->assertSeeInOrder([config('suiri.notes.privacy_work'), '見た・触れた・測った', 'ご本人の言葉']);
        $show('add-hypothesis')->assertSee('見立て（診断ではありません）')->assertSee(config('suiri.notes.privacy_work'));
        $show('predict-'.$hypothesis->id)->assertSee('次回、何が見えるはず？')->assertSee(config('suiri.notes.privacy_work'));
    }

    /** 事件を消すと、手がかり・見立て・予想と、予想から作った確かめるタスクも消えることを確かめる */
    public function test_destroy_removes_children_and_verify_tasks(): void
    {
        [$user, $case] = $this->board(true);
        $other = Task::factory()->for($user)->create(['title' => 'ふつうのタスク']);

        $this->actingAs($user)->delete(route('cases.destroy', $case))->assertRedirect(route('cases.index'));

        $this->assertModelMissing($case);
        $this->assertSame(0, Clue::count() + Hypothesis::count() + Prediction::count());
        $this->assertSame([$other->id], Task::pluck('id')->all());
    }

    /** 今日の画面の部品に、捜査中の事件のなぞ・手がかりと見立ての数・答え合わせ待ちの印が出ることを確かめる */
    public function test_today_open_cases_component(): void
    {
        [$user] = $this->board(true, ['title' => '植木の葉はなぜ黄色い？']);
        $cases = app(OpenCasesQuery::class)->get($user);

        $this->blade('<x-today.open-cases :cases="$cases" />', ['cases' => $cases])
            ->assertSee('植木の葉はなぜ黄色い？')->assertSee('事件 No.'.$cases->first()->id)
            ->assertSee('次は「答え合わせ」')->assertSee(config('suiri.case_list.due'));
        $this->blade('<x-today.open-cases :cases="$cases" />', ['cases' => collect()])
            ->assertSee(config('suiri.today.cases.empty'));
    }
}
