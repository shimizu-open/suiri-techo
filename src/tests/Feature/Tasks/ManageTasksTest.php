<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskKind;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** タスクを作る・直す・消す（T-1）と、きょうの画面の1行で足す欄の「くわしく」（くわしく作る画面へ題名を引き継ぐ） */
class ManageTasksTest extends TestCase
{
    use RefreshDatabase;

    /** ログインしていない人は、前のタスクの一覧の住所を開いてもトップへ戻されることを確かめる */
    public function test_guests_cannot_open_tasks(): void
    {
        $this->get(route('tasks.index'))->assertRedirect(route('home'));
    }

    /** 1行で足す欄（題名と期日だけ）で今日のものを作ると、ふつうのタスクができ、きょうの画面へ（枠を開かずに）戻ることを確かめる */
    public function test_a_task_can_be_quickly_added(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tasks.store'), ['title' => '  牛乳を買う ', 'due_on' => today()->toDateString()])
            ->assertRedirect(route('today'))->assertSessionHas('status');

        $task = $user->tasks()->sole();
        $this->assertSame('牛乳を買う', $task->title);
        $this->assertSame(today()->toDateString(), $task->due_on);
        $this->assertSame(TaskKind::Normal, $task->kind);
        $this->assertNull($task->starts_at);
    }

    /** きょうの画面の1行で足す欄の「今日」「明日」「いつか」で作ると、きょうの画面のそのタスクが見える枠（今日はなし・これから・いつか）へ戻ることを確かめる */
    public function test_adding_from_the_today_page_returns_to_its_box(): void
    {
        $this->actingAs($user = User::factory()->create());
        $add = fn (?string $due) => $this->post(route('tasks.store'), ['title' => '図書館に本を返す', 'due_on' => $due]);

        $add(today()->toDateString())->assertRedirect(route('today'));
        $add(today()->addDay()->toDateString())->assertRedirect(route('today', ['open' => 'upcoming']));
        $add('')->assertRedirect(route('today', ['open' => 'someday']));
        $this->assertSame(3, $user->tasks()->count());
    }

    /** くわしく作ると、メモ・開始時刻・予想時間も入り、期日が先なら「これから」、期日なしなら「いつか」の枠を開いたきょうの画面へ戻ることを確かめる */
    public function test_a_task_can_be_created_with_all_fields(): void
    {
        $user = User::factory()->create();
        $tomorrow = today()->addDay()->toDateString();

        $this->actingAs($user)->post(route('tasks.store'), [
            'title' => '週報を書く', 'memo' => '先週の分も', 'due_on' => $tomorrow, 'starts_at' => '09:30', 'duration_minutes' => '45',
            'kind' => 'verify', // 種類は送っても無視され、いつも「ふつう」になる
        ])->assertRedirect(route('today', ['open' => 'upcoming']));

        $task = $user->tasks()->sole();
        $this->assertSame(['先週の分も', $tomorrow, '09:30', 45], [$task->memo, $task->due_on, $task->starts_at, $task->duration_minutes]);
        $this->assertSame(TaskKind::Normal, $task->kind);

        $this->actingAs($user)->post(route('tasks.store'), ['title' => '本棚を片づける', 'due_on' => ''])
            ->assertRedirect(route('today', ['open' => 'someday']));
    }

    /** 題名が空・開始時刻だけで期日がない・時刻の形がちがう・予想時間が長すぎる、のときは作られないことを確かめる */
    public function test_invalid_tasks_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tasks.store'), [
            'title' => ' ', 'due_on' => '', 'starts_at' => '09:00', 'duration_minutes' => config('suiri.tasks.max_minutes') + 1,
        ])->assertSessionHasErrors(['title' => '題名を入力してください。', 'due_on', 'duration_minutes']);

        $this->actingAs($user)->post(route('tasks.store'), ['title' => '散歩', 'due_on' => '2026/10/31', 'starts_at' => '9時'])
            ->assertSessionHasErrors(['due_on', 'starts_at']);

        $this->assertSame(0, Task::count());
    }

    /** 「くわしく」を押すと、まだ作らずに、入れた題名と期日を持ってくわしく作る画面へ移ることを確かめる */
    public function test_the_detail_button_carries_the_title_to_the_create_page(): void
    {
        $user = User::factory()->create();
        $tomorrow = today()->addDay()->toDateString();

        $this->actingAs($user)->post(route('tasks.draft'), ['title' => '買い物', 'due_on' => $tomorrow])
            ->assertRedirect(route('tasks.create'));
        $this->assertSame(0, Task::count());

        $this->get(route('tasks.create'))->assertOk()
            ->assertSee('value="買い物"', false)->assertSee('value="'.$tomorrow.'"', false)
            ->assertDontSee('name="back"', false) // 戻り先はいつも、きょうの画面（受付が決める）なので送らない
            ->assertSee(config('suiri.notes.privacy'));
    }

    /** くわしく作る画面をそのまま開くと、題名は空、期日は今日が入っていることを確かめる */
    public function test_the_create_page_starts_with_today(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tasks.create'))->assertOk()
            ->assertSee('value="'.today()->toDateString().'"', false)
            ->assertDontSee('name="actual_minutes"', false);
    }

    /** 直す画面にいまの値ときょうの画面へもどるリンクが出て、直すと保存され、期日を消すと開始時刻も消え、「いつか」の枠へ戻ることを確かめる */
    public function test_a_task_can_be_updated(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->at('10:00', 30)->create(['title' => '牛乳を買う', 'memo' => '低脂肪']);

        $this->actingAs($user)->get(route('tasks.edit', $task))->assertOk()
            ->assertSee('value="牛乳を買う"', false)->assertSee('低脂肪')->assertSee('value="10:00"', false)
            ->assertDontSee('name="actual_minutes"', false) // まだ済んでいないので、かかった時間の欄はない
            ->assertSee(config('suiri.tasks.words.back'))->assertViewHas('backUrl', route('today')); // 今日のものなので、枠は開かない

        $this->patch(route('tasks.update', $task), [
            'title' => '牛乳とパンを買う', 'memo' => '', 'due_on' => '', 'starts_at' => '10:00', 'duration_minutes' => '20',
        ])->assertSessionHasErrors('due_on'); // 時刻だけ残すことはできない

        $this->patch(route('tasks.update', $task), ['title' => '牛乳とパンを買う', 'memo' => '', 'due_on' => '', 'duration_minutes' => '20'])
            ->assertRedirect(route('today', ['open' => 'someday']))->assertSessionHas('status');

        $task->refresh();
        $this->assertSame(['牛乳とパンを買う', null, null, null, 20], [$task->title, $task->memo, $task->due_on, $task->starts_at, $task->duration_minutes]);
    }

    /** 済んだタスクの直す画面には、かかった時間の欄が出て、直せることを確かめる */
    public function test_actual_minutes_can_be_edited_on_a_done_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->done()->create(['duration_minutes' => 30]);

        $this->actingAs($user)->get(route('tasks.edit', $task))->assertOk()->assertSee('name="actual_minutes"', false);

        $this->patch(route('tasks.update', $task), ['title' => $task->title, 'due_on' => $task->due_on, 'duration_minutes' => 30, 'actual_minutes' => 50])
            ->assertRedirect(route('today', ['open' => 'done']));
        $this->assertSame(50, $task->refresh()->actual_minutes);
    }

    /** タスクを消すと表からなくなり、きょうの画面のそのタスクがいた枠へ戻ることを確かめる */
    public function test_a_task_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->someday()->create();

        $this->actingAs($user)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('today', ['open' => 'someday']))->assertSessionHas('status');

        $this->assertModelMissing($task);
    }

    /** 期日を過ぎたものは、直すと上に出るきょうの画面へ（枠を開かずに）、明日より先のものは「これから」の枠へ戻り、直す画面のもどるリンクも同じ所を指すことを確かめる */
    public function test_updating_returns_to_the_box_by_due_date(): void
    {
        $user = User::factory()->create();
        $late = Task::factory()->for($user)->create(['due_on' => today()->subDays(3)->toDateString()]);
        $later = Task::factory()->for($user)->create(['due_on' => today()->addDays(5)->toDateString()]);

        $this->actingAs($user)->get(route('tasks.edit', $later))->assertOk()
            ->assertViewHas('backUrl', route('today', ['open' => 'upcoming']));
        $this->patch(route('tasks.update', $late), ['title' => $late->title, 'due_on' => $late->due_on])->assertRedirect(route('today'));
        $this->patch(route('tasks.update', $later), ['title' => $later->title, 'due_on' => $later->due_on])
            ->assertRedirect(route('today', ['open' => 'upcoming']));
    }
}
