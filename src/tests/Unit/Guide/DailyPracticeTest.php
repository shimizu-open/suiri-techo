<?php

namespace Tests\Unit\Guide;

use App\Models\User;
use App\Services\Guide\DailyPractice;
use App\Support\Guide\GuideLibrary;
use Mockery;
use Tests\TestCase;

/** 今日の練習を選ぶ係（DailyPractice）：同じ日には同じもの、日が変われば入れ替わる、メニューがなければ null */
class DailyPracticeTest extends TestCase
{
    /** 同じ人の同じ日なら、朝に選んでも夜に選んでも同じ練習メニューになることを確かめる */
    public function test_it_returns_the_same_practice_within_a_day(): void
    {
        $user = User::factory()->make(['id' => 7]);
        $daily = app(DailyPractice::class);
        $day = now()->setDate(2026, 11, 2);

        $morning = $daily->for($user, $day->copy()->setTime(7, 0));

        $this->assertNotNull($morning);
        $this->assertSame($morning, $daily->for($user, $day->copy()->setTime(22, 30)));
        $this->assertContains($morning['key'], array_column(app(GuideLibrary::class)->practices(), 'key'));
    }

    /** 30日分を選ぶと、毎日同じではなく、いくつかのメニューが入れ替わって出ることを確かめる */
    public function test_it_changes_from_day_to_day(): void
    {
        $user = User::factory()->make(['id' => 7]);
        $daily = app(DailyPractice::class);
        $picked = [];

        // 11月2日から30日分を1日ずつ選んで、出たメニューのキーを集める
        foreach (range(0, 29) as $offset) {
            $picked[] = $daily->for($user, now()->setDate(2026, 11, 2)->addDays($offset))['key'];
        }

        $this->assertGreaterThan(1, count(array_unique($picked)));
    }

    /** 練習メニューが1つもないときは、何も勧めない（null を返す）ことを確かめる */
    public function test_it_returns_null_without_practices(): void
    {
        $library = Mockery::mock(GuideLibrary::class);
        $library->shouldReceive('practices')->andReturn([]);

        $this->assertNull((new DailyPractice($library))->for(User::factory()->make(['id' => 1])));
    }
}
