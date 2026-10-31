<?php

namespace Tests\Feature\Review;

use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;
use App\Services\Review\Calibration;
use App\Services\Review\ReviewWeek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 確かさの答え合わせ（Calibration::for）が数える予想の範囲と、ふりかえりの画面の図と表 */
class CalibrationTest extends TestCase
{
    use RefreshDatabase;

    /** 今日を 2026-10-28（水）にする */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-28 12:00:00');
    }

    /** その人の予想を、確かさ・結果・答え合わせの日時を決めて作る */
    private function predict(User $user, int $confidence, ?string $result, ?string $resolvedAt = '2026-10-27 10:00:00', array $extra = []): Prediction
    {
        $case = CaseFile::factory()->for($user)->create();
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id]);

        return Prediction::factory()->create([
            'hypothesis_id' => $hypothesis->id, 'confidence' => $confidence, 'result' => $result,
            'resolved_at' => $result ? $resolvedAt : null,
        ] + $extra);
    }

    /** 数えるのは自分の当たり・外れだけで、どちらとも言えない・前提が崩れた・答え合わせ待ち・取り下げ・他人の予想は数えないことを確かめる */
    public function test_only_own_hits_and_misses_are_counted(): void
    {
        $user = User::factory()->create();
        $this->predict($user, 70, 'hit');
        $this->predict($user, 70, 'miss');
        $this->predict($user, 70, 'unclear');
        $this->predict($user, 70, 'void');
        $this->predict($user, 70, null);
        $this->predict($user, 70, 'hit', extra: ['withdrawn_at' => now()]);
        $this->predict(User::factory()->create(), 70, 'hit');

        $result = app(Calibration::class)->for($user);

        $this->assertSame(2, $result['counted']);
        $this->assertSame(2, $result['buckets'][2]['count']);
        $this->assertSame(1, $result['buckets'][2]['hits']);
    }

    /** 週を渡すと、その週に答え合わせした予想だけを数え、渡さなければこれまで全部を数えることを確かめる */
    public function test_week_range_uses_resolved_at(): void
    {
        $user = User::factory()->create();
        $this->predict($user, 80, 'hit', '2026-10-26 00:00:00');
        $this->predict($user, 80, 'miss', '2026-10-25 23:59:59');

        $this->assertSame(1, app(Calibration::class)->for($user, ReviewWeek::current())['counted']);
        $this->assertSame(2, app(Calibration::class)->for($user)['counted']);
    }

    /** 画面では、件数が足りた区間は割合を、足りない区間は「まだ判断できない」を、どの区間にも件数を出し、期間を切り替えられることを確かめる */
    public function test_review_page_shows_chart_and_table(): void
    {
        $user = User::factory()->create();
        // 確かさ 90 の予想を 5 件（4 件当たり）、確かさ 60 の予想を 2 件作る
        foreach ([true, true, true, true, false] as $hit) {
            $this->predict($user, 90, $hit ? 'hit' : 'miss', '2026-10-01 10:00:00');
        }
        $this->predict($user, 60, 'hit', '2026-10-27 10:00:00');
        $this->predict($user, 60, 'miss', '2026-10-27 10:00:00');

        $this->actingAs($user)->get(route('review.show'))->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('80％')
            ->assertSee('まだ判断できない')
            ->assertSee('5件中 4件が当たり')
            ->assertSeeInOrder(['数えたのは', '7件']);

        $this->actingAs($user)->get(route('review.show', ['range' => 'week']))->assertOk()
            ->assertSeeInOrder(['数えたのは', '2件'])
            ->assertDontSee('5件中 4件が当たり');
    }
}
