<?php

namespace Tests\Feature\Tasks;

use App\Actions\Tasks\DeleteTask;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/**
 * 予想から作った確かめるタスクを、ふつうの直す画面で直す・消すとき（予想の確かめる日とずらさない・答え合わせ待ちの予想だけを残さない）
 */
class VerifyTaskTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 確かめるタスクの期日・時刻を直すと、予想の確かめる日・時刻も同じになり、予想の文と確かさとタスクの題名は変わらないことを確かめる */
    public function test_moving_a_verify_task_moves_the_prediction_check(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        $task = $prediction->task;
        $title = $task->title;
        $tomorrow = today()->addDay()->toDateString();

        $this->actingAs($user)->patch(route('tasks.update', $task), [
            'title' => '後から書き換えた題名', 'due_on' => $tomorrow, 'starts_at' => '07:30', 'memo' => '朝ごはんのあと',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $prediction->refresh();
        $this->assertSame([$tomorrow, '07:30'], [$prediction->check_on, $prediction->check_at]);
        $this->assertSame(['前の晩に時間割を見れば、袋が玄関にあるはず', 70], [$prediction->statement, $prediction->confidence]);
        $task->refresh();
        $this->assertSame([$title, $tomorrow, '07:30', '朝ごはんのあと'], [$task->title, $task->due_on, $task->starts_at, $task->memo]);
    }

    /** 答え合わせ待ちの予想の確かめるタスクは、期日を空（いつか）にできず、予想の確かめる日も変わらないことを確かめる */
    public function test_a_pending_verify_task_needs_a_due_date(): void
    {
        [$user, , , , $prediction] = $this->board(true);

        $this->actingAs($user)->patch(route('tasks.update', $prediction->task), ['due_on' => ''])->assertSessionHasErrors('due_on');

        $this->assertSame(today()->toDateString(), $prediction->refresh()->check_on);
        $this->assertSame(today()->toDateString(), $prediction->task->refresh()->due_on);
    }

    /** 答え合わせ待ちの予想の確かめるタスクは消せず（403）、直す画面では題名が入力欄でなく、消す代わりに予想の取り下げが出ることを確かめる */
    public function test_a_pending_verify_task_cannot_be_deleted(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        $task = $prediction->task;

        $this->actingAs($user)->delete(route('tasks.destroy', $task))->assertForbidden();
        $this->assertModelExists($task);

        $this->actingAs($user)->get(route('tasks.edit', $task))->assertOk()
            ->assertDontSee('name="title"', false)->assertSee(config('suiri.tasks.words.title_fixed'))
            ->assertSee('確かめる日も一緒に変わるよ')
            ->assertSee(route('predictions.withdraw', $prediction))->assertSee('予想を取り下げる')
            ->assertDontSee(config('suiri.tasks.words.delete'));
    }

    /** タスクを消す用事を答え合わせ待ちの予想の確かめるタスクに直接使うと、消さずに止まることを確かめる（受付を通らない呼び方の守り） */
    public function test_the_delete_action_refuses_a_pending_verify_task(): void
    {
        [, , , , $prediction] = $this->board(true);

        // 消そうとして止まったら、タスクが残っているかを見る
        try {
            app(DeleteTask::class)->handle($prediction->task);
            $this->fail('LogicException が投げられるはず');
        } catch (LogicException) {
            $this->assertSame(1, Task::count());
        }
    }

    /** 答え合わせ済みの予想の確かめるタスクは、期日を直しても予想の確かめる日は変わらず（記録のまま）、消すこともできることを確かめる */
    public function test_a_resolved_verify_task_no_longer_moves_the_prediction(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'hit']);
        $task = $prediction->task->refresh();

        $this->actingAs($user)->patch(route('tasks.update', $task), ['due_on' => today()->addDays(3)->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(today()->toDateString(), $prediction->refresh()->check_on);

        $this->actingAs($user)->get(route('tasks.edit', $task))->assertSee(config('suiri.tasks.words.delete'));
        $this->actingAs($user)->delete(route('tasks.destroy', $task))->assertRedirect();
        $this->assertModelMissing($task);
        $this->assertModelExists($prediction);
    }

    /** 直す画面の取り下げボタンから予想を取り下げると、予想に取り下げた日時が入り、確かめるタスクが消えることを確かめる */
    public function test_withdrawing_from_the_task_page_removes_the_task(): void
    {
        [$user, , , , $prediction] = $this->board(true);

        $this->actingAs($user)->post(route('predictions.withdraw', $prediction))->assertRedirect();

        $this->assertNotNull($prediction->refresh()->withdrawn_at);
        $this->assertSame(0, Task::count());
    }
}
