<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** タスクの一覧の振り分け（今日／これから／いつか／済み。T-2）と並び順・件数 */
class TaskListTest extends TestCase
{
    use RefreshDatabase;

    /** タスクを1件ずつ、期日・時刻・済みかを変えて作る（題名で見分ける） */
    private function seedTasks(User $user): void
    {
        $today = today();
        Task::factory()->for($user)->create(['title' => '今日の終日', 'due_on' => $today->toDateString()]);
        Task::factory()->for($user)->at('08:00')->create(['title' => '今日の朝', 'due_on' => $today->toDateString()]);
        Task::factory()->for($user)->create(['title' => '昨日の残り', 'due_on' => $today->copy()->subDay()->toDateString()]);
        Task::factory()->for($user)->create(['title' => '明日のこと', 'due_on' => $today->copy()->addDay()->toDateString()]);
        Task::factory()->for($user)->create(['title' => '来週のこと', 'due_on' => $today->copy()->addWeek()->toDateString()]);
        Task::factory()->for($user)->someday()->create(['title' => 'いつかのこと']);
        Task::factory()->for($user)->create(['title' => '済んだこと', 'done_at' => now()->subDay()]);
        Task::factory()->for($user)->create(['title' => 'さっき済んだこと', 'done_at' => now()]);
    }

    /** 「今日」には期日が今日までの済んでいないものが、過ぎたもの → 終日 → 時刻順に並び、ほかは出ないことを確かめる */
    public function test_the_today_list_shows_due_and_overdue_tasks(): void
    {
        $user = User::factory()->create();
        $this->seedTasks($user);

        $this->actingAs($user)->get(route('tasks.index'))->assertOk()
            ->assertSeeInOrder(['昨日の残り', '今日の終日', '今日の朝'])
            ->assertSee('までだったよ')
            ->assertDontSee('明日のこと')->assertDontSee('いつかのこと')->assertDontSee('済んだこと');
    }

    /** 「これから」には期日が明日より先のものが日付順に並び、明日のものには「明日」と出ることを確かめる */
    public function test_the_upcoming_list_shows_future_tasks(): void
    {
        $user = User::factory()->create();
        $this->seedTasks($user);

        $this->actingAs($user)->get(route('tasks.index', ['view' => 'upcoming']))->assertOk()
            ->assertSeeInOrder(['明日のこと', '明日', '来週のこと'])
            ->assertDontSee('今日の朝')->assertDontSee('いつかのこと');
    }

    /** 「いつか」には期日のないものだけ、「済み」には済んだものが新しい順に出ることを確かめる */
    public function test_someday_and_done_lists(): void
    {
        $user = User::factory()->create();
        $this->seedTasks($user);

        $this->actingAs($user)->get(route('tasks.index', ['view' => 'someday']))->assertOk()
            ->assertSee('いつかのこと')->assertDontSee('明日のこと')->assertDontSee('今日の朝');

        $this->get(route('tasks.index', ['view' => 'done']))->assertOk()
            ->assertSeeInOrder(['さっき済んだこと', '済んだこと'])->assertDontSee('いつかのこと')
            ->assertSee('」をまだに戻す');
    }

    /** タブに、今日・これから・いつか のまだ済んでいない件数が出ることを確かめる */
    public function test_the_tabs_show_counts(): void
    {
        $user = User::factory()->create();
        $this->seedTasks($user);

        $this->actingAs($user)->get(route('tasks.index'))->assertOk()
            ->assertViewHas('counts', ['today' => 3, 'upcoming' => 2, 'someday' => 1]);
    }

    /** 知らない一覧の種類を渡したときは「今日」の一覧になることを確かめる */
    public function test_an_unknown_view_falls_back_to_today(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tasks.index', ['view' => 'all']))->assertOk()
            ->assertViewHas('view', 'today');
    }

    /** 一覧が空のときは、その種類の空の一言が出ることを確かめる */
    public function test_empty_lists_show_a_hint(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tasks.index', ['view' => 'someday']))->assertOk()
            ->assertSee(config('suiri.tasks.empty.someday.title'));
    }

    /** 済みの一覧は、決まった件数までしか出さないことを確かめる */
    public function test_the_done_list_is_limited(): void
    {
        config(['suiri.tasks.done_limit' => 2]);
        $user = User::factory()->create();
        Task::factory()->for($user)->count(3)->done()->create();

        $this->actingAs($user)->get(route('tasks.index', ['view' => 'done']))->assertOk()
            ->assertViewHas('tasks', fn ($tasks) => $tasks->count() === 2);
    }
}
