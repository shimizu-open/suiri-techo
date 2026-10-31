<?php

namespace Tests\Feature\Tasks;

use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** タスクの完了・未完了に戻す（T-3）、完了のときのかかった時間とさそう枠（T-5・T-8）、確かめるタスク（T-4） */
class CompleteTaskTest extends TestCase
{
    use RefreshDatabase;

    /** 済みにすると済んだ日時が入り、元の画面（開いていた枠ごと）へ戻って、きょうの画面に「実際は何分だった？」と「気になる」をさそう枠が出ることを確かめる */
    public function test_a_task_can_be_completed_and_the_follow_up_is_shown(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['title' => '買い物', 'duration_minutes' => 30]);

        $this->actingAs($user)->from(route('today', ['open' => 'upcoming']))->post(route('tasks.done', $task))
            ->assertRedirect(route('today', ['open' => 'upcoming']))->assertSessionHas('task_done', $task->id);
        $this->assertTrue($task->refresh()->isDone());

        $page = $this->get(route('today'))->assertOk()->assertViewHas('justDone', fn ($done) => $done?->is($task))
            ->assertSee('「買い物」、できたね！')->assertSee('実際は何分だった？')->assertSee('name="actual_minutes"', false)
            ->assertSee('name="actual_minutes" value="30"', false);
        // 事件を作る画面があれば、このタスクを出発点にした事件を作るボタンも出る（題名は URL ではなく POST の隠し項目で送る）
        if (Route::has('cases.draft')) {
            $page->assertSee('action="'.route('cases.draft').'"', false)->assertSee('name="label" value="買い物"', false)
                ->assertDontSee(e(route('cases.create', ['origin' => 'task', 'label' => '買い物'])), false);
        }
    }

    /** 済みにするときに、かかった時間も一緒に記録できることを確かめる */
    public function test_actual_minutes_can_be_sent_when_completing(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->actingAs($user)->post(route('tasks.done', $task), ['actual_minutes' => '40'])->assertRedirect();

        $this->assertTrue($task->refresh()->isDone());
        $this->assertSame(40, $task->actual_minutes);
    }

    /** 済んだあとに、さそう枠からかかった時間を足しても、済んだ日時は変わらず、枠に「30分の予定で、45分かかったね。」が出ることを確かめる */
    public function test_actual_minutes_can_be_added_after_completion(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['title' => '買い物', 'duration_minutes' => 30, 'done_at' => now()->subHour()]);
        $doneAt = $task->done_at->toDateTimeString();

        $this->actingAs($user)->from(route('today'))->post(route('tasks.done', $task), ['actual_minutes' => '45'])
            ->assertRedirect(route('today'));

        $task->refresh();
        $this->assertSame(45, $task->actual_minutes);
        $this->assertSame($doneAt, $task->done_at->toDateTimeString());

        $this->get(route('today'))->assertOk()->assertSeeInOrder(['30分', 'の予定で、', '45分', 'かかったね。'])
            ->assertDontSee('id="done-actual"', false);
    }

    /** かかった時間が0や長すぎるときは記録されないことを確かめる */
    public function test_invalid_actual_minutes_are_rejected(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->actingAs($user)->post(route('tasks.done', $task), ['actual_minutes' => '0'])->assertSessionHasErrors('actual_minutes');
        $this->actingAs($user)->post(route('tasks.done', $task), ['actual_minutes' => '99999'])->assertSessionHasErrors('actual_minutes');

        $this->assertFalse($task->refresh()->isDone());
    }

    /** 済んだタスクを未完了に戻せること、かかった時間は残ることを確かめる */
    public function test_a_done_task_can_be_reopened(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->done()->create(['actual_minutes' => 25]);

        $this->actingAs($user)->from(route('today', ['open' => 'done']))->delete(route('tasks.undone', $task))
            ->assertRedirect(route('today', ['open' => 'done']))->assertSessionHas('status');

        $task->refresh();
        $this->assertFalse($task->isDone());
        $this->assertSame(25, $task->actual_minutes);
    }

    /** 元の画面がわからない（前の住所がない）ときは、済ませても戻しても、きょうの画面へ戻ることを確かめる */
    public function test_without_a_previous_page_it_returns_to_the_today_page(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->actingAs($user)->post(route('tasks.done', $task))->assertRedirect(route('today'));
        $this->delete(route('tasks.undone', $task))->assertRedirect(route('today'));
    }

    /** 予想から作った確かめるタスクは、チェックで済みにも未完了にもできない（答え合わせで済みになる）ことを確かめる */
    public function test_verify_tasks_are_completed_only_by_resolving_the_prediction(): void
    {
        $prediction = Prediction::factory()->due()->create();
        $user = User::find($prediction->user_id);
        $open = Task::factory()->verify($prediction)->create();
        $done = Task::factory()->verify($prediction)->done()->create();

        $this->actingAs($user)->post(route('tasks.done', $open))->assertForbidden();
        $this->actingAs($user)->delete(route('tasks.undone', $done))->assertForbidden();

        $this->assertFalse($open->refresh()->isDone());
        $this->assertTrue($done->refresh()->isDone());
    }

    /** もとの予想が消えた確かめるタスクは、ふつうのタスクと同じようにチェックで済みにできることを確かめる */
    public function test_a_verify_task_without_its_prediction_can_be_completed(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['kind' => 'verify', 'prediction_id' => null]);

        $this->actingAs($user)->post(route('tasks.done', $task))->assertRedirect();

        $this->assertTrue($task->refresh()->isDone());
    }

    /** きょうの画面の「これから」の確かめるタスクの行には、済みのボタンの代わりに予想の画面の「答え合わせ」へのリンクが出ることを確かめる */
    public function test_verify_tasks_link_to_the_prediction(): void
    {
        $this->ensurePredictionRoute();
        $prediction = Prediction::factory()->create(['check_on' => today()->addDay()->toDateString()]);
        $task = Task::factory()->verify($prediction)->create();

        $this->actingAs(User::find($prediction->user_id))->get(route('today', ['open' => 'upcoming']))->assertOk()
            ->assertSee('確かめる')
            ->assertSee(route('predictions.show', $prediction->id), false)
            ->assertDontSee(route('tasks.done', $task), false);
    }

    /** 予想の画面（事件の担当）がまだなければ、テストの中だけで仮の住所を置く（リンクの形を確かめるため） */
    private function ensurePredictionRoute(): void
    {
        // すでに事件の担当が作っていれば、そのまま使う
        if (Route::has('predictions.show')) {
            return;
        }
        Route::get('/predictions/{prediction}', fn () => 'ok')->name('predictions.show');
        Route::getRoutes()->refreshNameLookups();
    }
}
