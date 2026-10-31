<?php

namespace Tests\Feature\Today;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ナビは4つ（きょう・事件・手引き・ふりかえり）。タスクの項目はなく、タスクを作る・直す画面では「きょう」が「いまここ」になる */
class TodayNavTest extends TestCase
{
    use RefreshDatabase;

    /** ナビの項目が4つで、先頭が「きょう」（今日の画面とタスクの画面の両方を受け持つ）、タスクの一覧の項目がないことを確かめる */
    public function test_the_nav_has_four_items_without_tasks(): void
    {
        $nav = collect(config('suiri.nav'));

        $this->assertCount(4, $nav);
        $this->assertSame(['today', 'cases.index', 'guide.index', 'review.show'], $nav->pluck('route')->all());
        $this->assertSame('きょう', $nav->first()['label']);
        $this->assertContains('tasks.*', $nav->first()['match']);
        $this->assertFalse($nav->contains('route', 'tasks.index'));
    }

    /** タスクを作る画面では、ナビの「きょう」のリンクに「いまここ」の印が付き、タスクの一覧へのリンクは出ないことを確かめる */
    public function test_today_is_current_on_the_task_pages(): void
    {
        $page = $this->actingAs(User::factory()->create())->get(route('tasks.create'))->assertOk()
            ->assertDontSee('href="'.route('tasks.index').'"', false);

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('today'), '#').'"\s+aria-current="page"#', $page->getContent());
    }
}
