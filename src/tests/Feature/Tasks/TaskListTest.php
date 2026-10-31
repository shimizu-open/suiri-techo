<?php

namespace Tests\Feature\Tasks;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 前のタスクの一覧の住所（/tasks?view=…）は、きょうの画面（/today?open=…）へ移る（一覧はきょうの画面にまとめた） */
class TaskListTest extends TestCase
{
    use RefreshDatabase;

    /** /tasks と ?view=today は、枠を開かずにきょうの画面へ移ることを確かめる */
    public function test_the_old_list_moves_to_the_today_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tasks.index'))->assertRedirect(route('today'));
        $this->get(route('tasks.index', ['view' => 'today']))->assertRedirect(route('today'));
    }

    /** これから・いつか・済み の一覧は、きょうの画面のその枠を開く ?open= に変わって移ることを確かめる */
    public function test_each_list_opens_the_same_box_on_the_today_page(): void
    {
        $this->actingAs(User::factory()->create());

        // 前の一覧の種類を1つずつ取り出し、同じ名前の枠を開く住所へ移るかを見る
        foreach (['upcoming', 'someday', 'done'] as $view) {
            $this->get(route('tasks.index', ['view' => $view]))->assertRedirect(route('today', ['open' => $view]));
        }
    }

    /** 知らない一覧の種類や、形のちがう値（配列）を渡したときは、枠を開かずにきょうの画面へ移ることを確かめる */
    public function test_an_unknown_view_moves_to_the_plain_today_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tasks.index', ['view' => 'all']))->assertRedirect(route('today'));
        $this->get(route('tasks.index').'?view[]=done')->assertRedirect(route('today'));
    }

    /** ログインしていない人は、移る前にトップへ戻されることを確かめる */
    public function test_guests_are_sent_home(): void
    {
        $this->get(route('tasks.index', ['view' => 'done']))->assertRedirect(route('home'));
    }
}
