<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 他人のタスクは、見る・直す・消す・済みにする・戻すことができない（持ち主だけ。Policy） */
class TaskOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /** 他人のタスクの直す画面・直す・消す・済みにする・戻すは、どれも 403 になり、タスクは変わらないことを確かめる */
    public function test_strangers_cannot_touch_someone_elses_task(): void
    {
        $task = Task::factory()->create(['title' => '人のタスク']);
        $doneTask = Task::factory()->for($task->user)->done()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger);
        $this->get(route('tasks.edit', $task))->assertForbidden();
        $this->patch(route('tasks.update', $task), ['title' => '書きかえ'])->assertForbidden();
        $this->delete(route('tasks.destroy', $task))->assertForbidden();
        $this->post(route('tasks.done', $task))->assertForbidden();
        $this->delete(route('tasks.undone', $doneTask))->assertForbidden();

        $this->assertSame('人のタスク', $task->refresh()->title);
        $this->assertFalse($task->isDone());
        $this->assertTrue($doneTask->refresh()->isDone());
    }

    /** きょうの画面（前の一覧をまとめた所）には、自分のタスクだけが出て、他人のタスクは出ないことを確かめる */
    public function test_lists_show_only_my_tasks(): void
    {
        $me = User::factory()->create();
        Task::factory()->for($me)->create(['title' => '自分のタスク']);
        Task::factory()->create(['title' => '人のタスク']);
        Task::factory()->someday()->create(['title' => '人のいつか']);

        $this->actingAs($me)->get(route('today', ['open' => 'someday']))->assertOk()
            ->assertSee('自分のタスク')->assertDontSee('人のタスク')->assertDontSee('人のいつか')
            ->assertViewHas('someday', fn ($tasks) => $tasks->isEmpty());
    }

    /** 他人の済んだタスクを、さそう枠の id として渡しても出ないことを確かめる */
    public function test_the_follow_up_ignores_someone_elses_task(): void
    {
        $other = Task::factory()->done()->create(['title' => '人の済んだタスク']);

        $this->actingAs(User::factory()->create())->withSession(['task_done' => $other->id])
            ->get(route('today'))->assertOk()->assertDontSee('人の済んだタスク')->assertViewHas('justDone', null);
    }
}
