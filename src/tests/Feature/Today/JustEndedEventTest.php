<?php

namespace Tests\Feature\Today;

use App\Contracts\CalendarAgenda;
use App\Models\User;
use App\Queries\Today\AgendaQuery;
use App\Services\Google\FakeCalendarAgenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** さっき終わった予定への声かけ（S-6）：終わってから3時間以内の今日の予定にだけ印を付け、「気になる」を目立たせる */
class JustEndedEventTest extends TestCase
{
    use RefreshDatabase;

    /** 時計を 2026-10-31 15:00 に止め、今日の予定を偽物にする（1時間前に終わった・6時間前に終わった・これから・終日） */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(15, 0));
        $this->app->instance(CalendarAgenda::class, new FakeCalendarAgenda(today: [
            ['title' => '文化祭の準備', 'starts_at' => null, 'ends_at' => null, 'all_day' => true],
            ['title' => '朝礼', 'starts_at' => '08:00', 'ends_at' => '09:00', 'all_day' => false],
            ['title' => '保護者会', 'starts_at' => '13:00', 'ends_at' => '14:00', 'all_day' => false],
            ['title' => '夕方の打ち合わせ', 'starts_at' => '16:00', 'ends_at' => '17:00', 'all_day' => false],
        ]));
    }

    /** 終わってから3時間以内の予定だけに印が付き、終日・前に終わった・これからの予定には付かないことを確かめる */
    public function test_only_recently_ended_events_are_marked(): void
    {
        $items = app(AgendaQuery::class)->for(User::factory()->create(), '2026-10-31');

        $this->assertSame(
            ['文化祭の準備' => false, '朝礼' => false, '保護者会' => true, '夕方の打ち合わせ' => false],
            $items->mapWithKeys(fn (array $item) => [$item['title'] => $item['just_ended']])->all(),
        );
    }

    /** 今日の画面では、さっき終わった予定に声かけの印が1つだけ出て、その行の「気になる」が目立つことを確かめる */
    public function test_today_page_shows_the_prompt_once(): void
    {
        $page = $this->actingAs(User::factory()->create())->get(route('today'))->assertOk()
            ->assertSeeInOrder(['保護者会', config('suiri.suggest.just_ended_label'), '「保護者会（13:00）」から気になることを記録'])
            ->getContent();

        $this->assertSame(1, substr_count($page, config('suiri.suggest.just_ended_label')));
        $this->assertSame(1, substr_count($page, 'border-accent bg-accent-soft text-accent'));
    }

    /** 終わってから3時間を過ぎた予定の印は消え、あとから終わった予定に印が移ることを確かめる */
    public function test_the_mark_moves_after_three_hours(): void
    {
        $this->travelTo(now()->setTime(17, 1));

        $items = app(AgendaQuery::class)->for(User::factory()->create(), '2026-10-31')->pluck('just_ended', 'title');

        $this->assertFalse($items['保護者会']);
        $this->assertTrue($items['夕方の打ち合わせ']);
    }
}
