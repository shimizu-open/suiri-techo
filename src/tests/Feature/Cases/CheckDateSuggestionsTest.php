<?php

namespace Tests\Feature\Cases;

use App\Contracts\CalendarAgenda;
use App\Models\CalendarConnection;
use App\Models\User;
use App\Services\Google\FakeCalendarAgenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 確かめる日の提案（S-4）：すぐ選べる日と、事件の言葉と題名が重なる自分の予定（つないでいる人だけ・題名は保存しない） */
class CheckDateSuggestionsTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    private FakeCalendarAgenda $agenda;

    /** 時計を 2026-10-31（土）に止め、予定の窓口を偽物にする（なぞ「体操着をなぜ忘れる？」に重なる予定と、重ならない予定） */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(9, 0));
        $this->agenda = new FakeCalendarAgenda(upcoming: [
            ['title' => '体育', 'date' => '2026-11-02', 'starts_at' => '10:40', 'all_day' => false],
            ['title' => '体操着の洗濯', 'date' => '2026-11-03', 'starts_at' => '19:00', 'all_day' => false],
            ['title' => '歯医者', 'date' => '2026-11-04', 'starts_at' => '16:00', 'all_day' => false],
            ['title' => '体操着の洗濯', 'date' => '2026-11-10', 'starts_at' => '19:00', 'all_day' => false],
            ['title' => '時間割の配布', 'date' => '2026-11-06', 'starts_at' => null, 'all_day' => true],
        ]);
        $this->app->instance(CalendarAgenda::class, $this->agenda);
    }

    /** カレンダーとつないだ人と、その人の事件（手がかり・見立てつき）を作る */
    private function connectedBoard(): array
    {
        [$user, $case, , $hypothesis] = $this->board(caseAttributes: ['title' => '体操着をなぜ忘れる？']);
        CalendarConnection::factory()->for($user)->create();

        return [$user, $case, $hypothesis];
    }

    /** 「明日」「3日後」「1週間後」のボタンが、それぞれの日付を持って出ることを確かめる */
    public function test_quick_dates_are_offered(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSee('data-date="2026-11-01"', false)->assertSee('明日')
            ->assertSee('data-date="2026-11-03"', false)->assertSee('3日後')
            ->assertSee('data-date="2026-11-07"', false)->assertSee('1週間後');
    }

    /** なぞ・手がかり・見立てと言葉が重なる予定だけが「次の『◯◯』の日」として出て、同じ題名はいちばん近い日だけになることを確かめる */
    public function test_only_overlapping_events_are_offered(): void
    {
        [$user, $case] = $this->connectedBoard();

        $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSee('次の『体操着の洗濯』の日（11/3 火）')
            ->assertSee('data-date="2026-11-03" data-time="19:00"', false)
            ->assertSee('次の『時間割の配布』の日（11/6 金）')
            ->assertDontSee('『体育』')
            ->assertDontSee('歯医者')
            ->assertDontSee('data-date="2026-11-10"', false);
        $this->assertSame(1, $this->agenda->upcomingCalls);
    }

    /** 重なる予定が多くても、出すのは3件までであることを確かめる */
    public function test_at_most_three_events(): void
    {
        [$user, $case] = $this->connectedBoard();
        $this->agenda->upcoming = array_map(fn ($day) => [
            'title' => "体操着チェック{$day}", 'date' => "2026-11-0{$day}", 'starts_at' => null, 'all_day' => true,
        ], range(1, 5));

        $page = $this->actingAs($user)->get(route('cases.show', $case))->getContent();

        $this->assertSame(3, substr_count($page, '次の『体操着チェック'));
    }

    /** カレンダーとつないでいない人には予定の提案を出さず、予定も読まないことを確かめる */
    public function test_no_calendar_suggestions_without_a_connection(): void
    {
        [$user, $case] = $this->board(caseAttributes: ['title' => '体操着をなぜ忘れる？']);

        $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSee('明日')
            ->assertDontSee('次の『');
        $this->assertSame(0, $this->agenda->upcomingCalls);
    }

    /** 提案から入れた日付と時刻で予想を立てられ、予定の題名はどの表にも保存されないことを確かめる */
    public function test_event_titles_are_never_saved(): void
    {
        [$user, $case, $hypothesis] = $this->connectedBoard();
        $this->actingAs($user)->get(route('cases.show', $case))->assertSee('体操着の洗濯');

        $this->post(route('predictions.store', $hypothesis), [
            'form_key' => 'predict-'.$hypothesis->id,
            'statement' => '前の晩に時間割を見れば、袋が玄関にあるはず',
            'confidence' => 70,
            'check_on' => '2026-11-03',
            'check_at' => '19:00',
        ])->assertRedirect();

        $prediction = $hypothesis->predictions()->sole();
        $this->assertSame(['2026-11-03', '19:00'], [$prediction->check_on, $prediction->check_at]);
        // すべての表のすべての行を文字にして、予定の題名が入っていないことを確かめる
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table'") as $table) {
            $rows = json_encode(DB::table($table->name)->get(), JSON_UNESCAPED_UNICODE);
            $this->assertStringNotContainsString('洗濯', $rows, "{$table->name} に予定の題名が入っている");
        }
    }

    /** ほかの人の事件の画面は開けず、その人の予定を読みに行かないことを確かめる */
    public function test_other_users_case_reads_nothing(): void
    {
        [, $case] = $this->connectedBoard();

        $this->actingAs(User::factory()->create())->get(route('cases.show', $case))->assertForbidden();
        $this->assertSame(0, $this->agenda->upcomingCalls);
    }
}
