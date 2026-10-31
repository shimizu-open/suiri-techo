<?php

namespace Tests\Feature\Today;

use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use App\Queries\Tasks\TaskListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * きょうの画面に渡すタスクの枠（期日を過ぎた・これから・いつか・済み）と件数・開く枠（open）・1行で足す欄の日付（dates）。
 * 振り分けは Queries/Tasks/TaskListQuery、受付は TodayController（見た目は TodayMergedLayoutTest）
 */
class TodayTaskListsTest extends TestCase
{
    use RefreshDatabase;

    /** 期日（今日からの日数。null ならいつか）と済んだ日時を変えて、題名で見分けられるタスクを作る */
    private function task(User $user, string $title, ?int $days, array $attributes = []): Task
    {
        return Task::factory()->for($user)->create([
            'title' => $title, 'due_on' => $days === null ? null : today()->addDays($days)->toDateString(), ...$attributes,
        ]);
    }

    /** きょうの画面の枠を、その人について組み立てる */
    private function lists(User $user): array
    {
        return app(TaskListQuery::class)->forToday($user, today()->toDateString());
    }

    /** 期日を過ぎた済んでいないものが古い順に入り、予想に結びついた確かめるタスクも入り、済んだもの・今日のものは入らないことを確かめる */
    public function test_overdue_holds_open_tasks_before_today(): void
    {
        $user = User::factory()->create();
        $this->task($user, '昨日の残り', -1);
        $this->task($user, '三日前の朝', -3, ['starts_at' => '08:00']);
        $this->task($user, '三日前の終日', -3);
        $this->task($user, '昨日済んだ', -1, ['done_at' => now()]);
        $this->task($user, '今日のこと', 0);
        $prediction = Prediction::factory()->create(['check_on' => today()->subDays(2)->toDateString()]);
        $verify = Task::factory()->verify($prediction)->create();

        $mine = $this->lists($user)['overdue'];
        $this->assertSame(['三日前の終日', '三日前の朝', '昨日の残り'], $mine->pluck('title')->all());
        $this->assertTrue($this->lists(User::find($prediction->user_id))['overdue']->contains($verify));
    }

    /** これからには明日より先の済んでいないものが、先の日付でも区切らずに日付ごと・時刻順に入ることを確かめる（済んだものと今日のものは入らない） */
    public function test_upcoming_is_grouped_by_date_without_a_cutoff(): void
    {
        $user = User::factory()->create();
        $this->task($user, '14日後', 14);
        $this->task($user, '明日の9時', 1, ['starts_at' => '09:00']);
        $this->task($user, '明日の終日', 1);
        $this->task($user, '40日後', 40);
        $this->task($user, '明日済んだ', 1, ['done_at' => now()]);
        $this->task($user, '今日のこと', 0);

        $upcoming = $this->lists($user)['upcoming'];
        $this->assertSame([today()->addDay()->toDateString(), today()->addDays(14)->toDateString(), today()->addDays(40)->toDateString()], $upcoming->keys()->all());
        $this->assertSame(['明日の終日', '明日の9時'], $upcoming->first()->pluck('title')->all());
        $this->assertSame(['40日後'], $upcoming->last()->pluck('title')->all());
    }

    /** いつかには期日のない済んでいないものが作った順に、済みにはこの7日に済んだものが新しい順に入り、8日前のものは入らないことを確かめる */
    public function test_someday_and_done(): void
    {
        $user = User::factory()->create();
        $this->task($user, '先にいつか', null);
        $this->task($user, 'あとでいつか', null);
        $this->task($user, '済んだいつか', null, ['done_at' => now()]);
        $this->task($user, '6日前に済んだ', -6, ['done_at' => today()->subDays(6)->setTime(0, 5)]);
        $this->task($user, '7日前に済んだ', -7, ['done_at' => today()->subDays(7)->setTime(23, 55)]);
        $this->task($user, 'さっき済んだ', 0, ['done_at' => now()->addSecond()]);

        $lists = $this->lists($user);
        $this->assertSame(['先にいつか', 'あとでいつか'], $lists['someday']->pluck('title')->all());
        $this->assertSame(['さっき済んだ', '済んだいつか', '6日前に済んだ'], $lists['done']->pluck('title')->all());
    }

    /** 済みは決まった件数（新しい順）までにし、件数は4つの枠の数（これからは日数でなくタスクの数）になることを確かめる */
    public function test_done_is_limited_and_counts_match_the_boxes(): void
    {
        config(['suiri.today_tasks.done_limit' => 2]);
        $user = User::factory()->create();
        Task::factory()->for($user)->count(3)->done()->create();
        $this->task($user, '明日A', 1);
        $this->task($user, '明日B', 1);
        $this->task($user, '来週', 7);
        $this->task($user, 'いつか', null);
        $this->task($user, '昨日', -1);

        $lists = $this->lists($user);
        $this->assertCount(2, $lists['done']);
        $this->assertSame(['upcoming' => 3, 'someday' => 1, 'done' => 2, 'overdue' => 1], $lists['counts']);
    }

    /** きょうの画面には、前からのキーに加えて枠・件数・日付が渡り、どの枠にもほかの人のタスクは入らないことを確かめる */
    public function test_the_page_gets_only_my_tasks_with_counts_and_dates(): void
    {
        $me = User::factory()->create();
        $this->task($me, '自分のいつか', null);
        $other = User::factory()->create();
        $this->task($other, '人の昨日', -1);
        $this->task($other, '人の明日', 1);
        $this->task($other, '人のいつか', null);
        $this->task($other, '人の済んだ', 0, ['done_at' => now()]);

        $this->actingAs($me)->get(route('today'))->assertOk()
            ->assertViewHasAll(['user', 'today', 'predictions', 'items', 'practice', 'cases', 'justDone'])
            ->assertViewHas('overdue', fn ($tasks) => $tasks->isEmpty())
            ->assertViewHas('upcoming', fn ($groups) => $groups->isEmpty())
            ->assertViewHas('someday', fn ($tasks) => $tasks->pluck('title')->all() === ['自分のいつか'])
            ->assertViewHas('done', fn ($tasks) => $tasks->isEmpty())
            ->assertViewHas('counts', ['upcoming' => 0, 'someday' => 1, 'done' => 0, 'overdue' => 0])
            ->assertViewHas('dates', ['today' => today()->toDateString(), 'tomorrow' => today()->addDay()->toDateString()]);
    }

    /** ?open= は upcoming・someday・done のときだけその値になり、ないとき・知らない値・配列のときは null になることを確かめる */
    public function test_open_is_one_of_the_boxes_or_null(): void
    {
        $this->actingAs(User::factory()->create());

        // 開ける枠の名前を1つずつ取り出し、そのまま渡るかを見る
        foreach (['upcoming', 'someday', 'done'] as $open) {
            $this->get(route('today', ['open' => $open]))->assertOk()->assertViewHas('open', $open);
        }
        $this->get(route('today'))->assertOk()->assertViewHas('open', null);
        $this->get(route('today', ['open' => 'today']))->assertOk()->assertViewHas('open', null);
        $this->get(route('today').'?open[]=done')->assertOk()->assertViewHas('open', null);
    }
}
