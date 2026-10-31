<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use App\Queries\Tasks\SimilarTasksQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 見積もりの答え合わせ（T-8）：予想時間の欄の近くに出す「題名の似た過去のタスクの実績」 */
class SimilarTasksTest extends TestCase
{
    use RefreshDatabase;

    /** 済んでいて、かかった時間の入ったタスクを作る */
    private function past(User $user, string $title, ?int $planned, ?int $actual, string $doneAgo = '1 day'): Task
    {
        return Task::factory()->for($user)->create([
            'title' => $title, 'duration_minutes' => $planned, 'actual_minutes' => $actual, 'done_at' => now()->sub($doneAgo),
        ]);
    }

    /** 同じ題名・含む題名・2文字ずつの組が重なる題名は似ているとし、似ていないもの・かかった時間がないもの・済んでいないもの・他人のものは出さないことを確かめる */
    public function test_it_finds_similar_done_tasks_of_the_same_person(): void
    {
        $user = User::factory()->create();
        $same = $this->past($user, '買い物', 30, 45, '2 days');
        $contains = $this->past($user, '買い物（スーパー）', 20, 25, '3 days');
        $this->past($user, '牛乳を買う', 10, 15);              // 似ていない
        $this->past($user, '買い物', 30, null);                // かかった時間がない
        Task::factory()->for($user)->create(['title' => '買い物', 'actual_minutes' => 40]); // まだ済んでいない
        $this->past(User::factory()->create(), '買い物', 5, 5); // 他人のもの

        $found = app(SimilarTasksQuery::class)->for($user, ' 買い物 ');

        $this->assertSame([$same->id, $contains->id], $found->pluck('id')->all());
    }

    /** 似ている度合いが同じなら新しいものが先に来て、出す数は config の件数までであることを確かめる */
    public function test_results_are_newest_first_and_limited(): void
    {
        config(['suiri.tasks.similar.limit' => 2]);
        $user = User::factory()->create();
        $old = $this->past($user, '週報を書く', 30, 60, '3 days');
        $new = $this->past($user, '週報を書く', 30, 40, '1 day');
        $mid = $this->past($user, '週報を書く', 30, 50, '2 days');

        $found = app(SimilarTasksQuery::class)->for($user, '週報を書く');

        $this->assertSame([$new->id, $mid->id], $found->pluck('id')->all());
        $this->assertNotContains($old->id, $found->pluck('id'));
    }

    /** 題名が空・記号だけのときは何も返さないことを確かめる */
    public function test_blank_titles_find_nothing(): void
    {
        $user = User::factory()->create();
        $this->past($user, '買い物', 30, 45);

        $this->assertCount(0, app(SimilarTasksQuery::class)->for($user, ''));
        $this->assertCount(0, app(SimilarTasksQuery::class)->for($user, '・！'));
    }

    /** 直す画面の「何分かかりそう？」の下に「前の『買い物』は 30分の予定で、45分かかったよ（15分長かった）」の一言が出て、そのタスク自身は出ないことを確かめる */
    public function test_the_edit_page_shows_past_estimates(): void
    {
        $user = User::factory()->create();
        $this->past($user, '買い物', 30, 45);
        $this->past($user, '買い物に行く', null, 90, '2 days');
        $task = Task::factory()->for($user)->create(['title' => '買い物']);

        $this->actingAs($user)->get(route('tasks.edit', $task))->assertOk()
            ->assertSeeInOrder(['何分かかりそう？', '前の『買い物』は', '30分', 'の予定で、', '45分', 'かかったよ', '（15分長かった）'])
            ->assertSeeInOrder(['の『買い物に行く』は', '1時間30分', 'かかったよ']);
    }

    /** 「くわしく」で題名を持ってきたくわしく作る画面にも、似たタスクの実績が出ることを確かめる */
    public function test_the_create_page_shows_past_estimates_for_the_carried_title(): void
    {
        $user = User::factory()->create();
        $this->past($user, '買い物', 60, 50);

        $this->actingAs($user)->post(route('tasks.draft'), ['title' => '買い物', 'due_on' => today()->toDateString()]);
        $this->get(route('tasks.create'))->assertOk()
            ->assertSeeInOrder(['前の『買い物』は', '1時間', 'の予定で、', '50分', 'で済んだよ', '（10分早かった）']);
    }

    /** 似たタスクがなければ、実績の枠そのものを出さないことを確かめる */
    public function test_nothing_is_shown_without_similar_tasks(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['title' => '散歩']);

        $this->actingAs($user)->get(route('tasks.edit', $task))->assertOk()->assertDontSee('の予定で、')->assertDontSee('かかったよ');
    }
}
