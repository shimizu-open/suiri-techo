<?php

namespace Tests\Feature\Review;

use App\Models\AtticNote;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\HypothesisStatusLog;
use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use App\Services\Review\ReviewWeek;
use App\Services\Review\WeeklyStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 週のふりかえりの数字（WeeklyStats・EstimateCheck）。週の境目と、他人の記録を数えないこと */
class WeeklyStatsTest extends TestCase
{
    use RefreshDatabase;

    /** 今日を 2026-10-28（水）にする。この週は 10月26日（月）0時〜11月2日（月）0時の手前 */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-28 12:00:00');
    }

    /** 2026-10-26 の週の数字を数える */
    private function stats(User $user): array
    {
        return app(WeeklyStats::class)->for($user, ReviewWeek::fromParam('2026-10-26'));
    }

    /** その人の事件に見立てを1つ作って返す */
    private function hypothesis(User $user, array $caseAttributes = []): Hypothesis
    {
        $case = CaseFile::factory()->for($user)->create($caseAttributes + ['created_at' => '2026-10-01 10:00:00']);

        return Hypothesis::factory()->create(['case_file_id' => $case->id]);
    }

    /** 事件は「月曜 0 時ちょうど」から「次の月曜 0 時の手前」までを数え、前後の週と他人の事件は数えないことを確かめる */
    public function test_cases_are_counted_within_the_week_boundaries(): void
    {
        $user = User::factory()->create();
        // 境目の前後に1件ずつ作る（週の中は 10/26 0:00 と 11/1 23:59:59 の2件）
        foreach (['2026-10-25 23:59:59', '2026-10-26 00:00:00', '2026-11-01 23:59:59', '2026-11-02 00:00:00'] as $at) {
            CaseFile::factory()->for($user)->create(['created_at' => $at]);
        }
        CaseFile::factory()->create(['created_at' => '2026-10-27 10:00:00']);

        $stats = $this->stats($user);

        $this->assertSame(2, $stats['cases']);
        $this->assertFalse($stats['is_empty']);
    }

    /** 見立てを2つ以上立てた事件の割合は、その週に作った事件のうち見立て（消えたものも）が2つ以上ある事件の割合になることを確かめる */
    public function test_share_of_cases_with_two_or_more_hypotheses(): void
    {
        $user = User::factory()->create();
        $two = CaseFile::factory()->for($user)->create(['created_at' => '2026-10-27 09:00:00']);
        Hypothesis::factory()->create(['case_file_id' => $two->id]);
        Hypothesis::factory()->status('eliminated')->create(['case_file_id' => $two->id]);
        $one = CaseFile::factory()->for($user)->create(['created_at' => '2026-10-27 10:00:00']);
        Hypothesis::factory()->create(['case_file_id' => $one->id]);
        $old = CaseFile::factory()->for($user)->create(['created_at' => '2026-10-10 10:00:00']);
        Hypothesis::factory()->count(3)->create(['case_file_id' => $old->id]);

        $this->assertSame(['count' => 1, 'total' => 2, 'rate' => 50], $this->stats($user)['multi_hypothesis']);
    }

    /** 答え合わせした予想は、その週に答え合わせしたものを結果ごとに数え、前の週・他人・答え合わせ待ちは数えないことを確かめる */
    public function test_resolved_predictions_are_counted_by_result(): void
    {
        $user = User::factory()->create();
        $hypothesis = $this->hypothesis($user);
        // 結果と答え合わせの日時の組を1つずつ作る（最後の2つは前の週・答え合わせ待ち）
        foreach ([['hit', '2026-10-26 08:00:00'], ['hit', '2026-10-30 20:00:00'], ['miss', '2026-10-28 09:00:00'],
            ['unclear', '2026-10-29 09:00:00'], ['void', '2026-11-01 22:00:00'], ['miss', '2026-10-25 22:00:00'], [null, null]] as [$result, $at]) {
            Prediction::factory()->create(['hypothesis_id' => $hypothesis->id, 'result' => $result, 'resolved_at' => $at]);
        }
        Prediction::factory()->resolved('hit')->create(['resolved_at' => '2026-10-27 10:00:00']);

        $resolved = $this->stats($user)['resolved'];

        $this->assertSame(5, $resolved['total']);
        $this->assertSame(['hit' => 2, 'miss' => 1, 'unclear' => 1, 'void' => 1], $resolved['by_result']);
    }

    /** 考えを変えた回数は、その週の自分の hypothesis_status_logs の数で、前の週と他人の記録は数えないことを確かめる */
    public function test_mind_changes_count_status_logs_in_the_week(): void
    {
        $user = User::factory()->create();
        $hypothesis = $this->hypothesis($user);
        HypothesisStatusLog::factory()->count(2)->create(['hypothesis_id' => $hypothesis->id, 'created_at' => '2026-10-27 10:00:00']);
        HypothesisStatusLog::factory()->create(['hypothesis_id' => $hypothesis->id, 'created_at' => '2026-10-20 10:00:00']);
        HypothesisStatusLog::factory()->create(['created_at' => '2026-10-27 10:00:00']);

        $this->assertSame(2, $this->stats($user)['mind_changes']);
    }

    /** 外れから学んだことは、その週の外れを見落としの種類ごとに数の多い順で返し、選ばなかった外れは種類なしで数えることを確かめる */
    public function test_miss_reasons_are_grouped(): void
    {
        $user = User::factory()->create();
        $hypothesis = $this->hypothesis($user);
        // 見落としの種類を1つずつ外れの予想にする（思い込み2件・偶然1件・選ばなかった1件）
        foreach (['assumption', 'assumption', 'chance', null] as $reason) {
            Prediction::factory()->resolved('miss', $reason)->create(['hypothesis_id' => $hypothesis->id, 'resolved_at' => '2026-10-29 10:00:00']);
        }

        $misses = $this->stats($user)['misses'];

        $this->assertCount(3, $misses);
        $this->assertSame('assumption', $misses[0]['reason']->value);
        $this->assertSame(2, $misses[0]['count']);
        $this->assertContains(null, array_column($misses, 'reason'));
    }

    /** 屋根裏の棚に増えた知識は、その週に足した自分の知識だけを数えることを確かめる */
    public function test_attic_notes_added_in_the_week(): void
    {
        $user = User::factory()->create();
        AtticNote::factory()->for($user)->create(['rule' => '今週の知識', 'created_at' => '2026-10-27 10:00:00']);
        AtticNote::factory()->for($user)->create(['rule' => '先週の知識', 'created_at' => '2026-10-19 10:00:00']);
        AtticNote::factory()->create(['rule' => '他人の知識', 'created_at' => '2026-10-27 10:00:00']);

        $attic = $this->stats($user)['attic'];

        $this->assertSame(1, $attic['count']);
        $this->assertSame(['今週の知識'], $attic['recent']->pluck('rule')->all());
    }

    /** 見積もりの答え合わせは、その週に済んだ自分のタスクのうち予想時間とかかった時間の両方があるものだけを数えることを確かめる */
    public function test_estimates_use_tasks_done_in_the_week_with_both_minutes(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['done_at' => '2026-10-27 10:00:00', 'duration_minutes' => 30, 'actual_minutes' => 50]);
        Task::factory()->for($user)->create(['done_at' => '2026-10-31 10:00:00', 'duration_minutes' => 60, 'actual_minutes' => 50]);
        Task::factory()->for($user)->create(['done_at' => '2026-10-27 10:00:00', 'duration_minutes' => 30, 'actual_minutes' => null]);
        Task::factory()->for($user)->create(['done_at' => '2026-10-24 10:00:00', 'duration_minutes' => 30, 'actual_minutes' => 90]);
        Task::factory()->create(['done_at' => '2026-10-27 10:00:00', 'duration_minutes' => 10, 'actual_minutes' => 90]);

        $this->assertSame(
            ['count' => 2, 'longer' => 1, 'longer_rate' => 50, 'average_diff' => 5],
            $this->stats($user)['estimates'],
        );
    }

    /** 記録が何もない週は、数が 0 で、割合は null（画面では「—」）になり、空の週の印が立つことを確かめる */
    public function test_an_empty_week_has_zeros_and_no_rates(): void
    {
        $stats = $this->stats(User::factory()->create());

        $this->assertTrue($stats['is_empty']);
        $this->assertSame(0, $stats['cases']);
        $this->assertNull($stats['multi_hypothesis']['rate']);
        $this->assertNull($stats['estimates']['average_diff']);
    }
}
