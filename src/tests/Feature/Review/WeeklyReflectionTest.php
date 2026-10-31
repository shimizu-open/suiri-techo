<?php

namespace Tests\Feature\Review;

use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\HypothesisStatusLog;
use App\Models\User;
use App\Models\WeeklyReflection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 週の問い（PUT /review/{week}）：保存と上書き・選べる事件・他人の事件を選べないこと */
class WeeklyReflectionTest extends TestCase
{
    use RefreshDatabase;

    /** 今日を 2026-10-28（水）にする。今週は 2026-10-26 */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-28 12:00:00');
    }

    /** 週の問いを保存すると、その週の月曜日の行ができ、もう一度保存すると同じ行が上書きされることを確かめる */
    public function test_reflection_is_saved_and_overwritten(): void
    {
        $user = User::factory()->create();
        $case = CaseFile::factory()->for($user)->create(['created_at' => '2026-10-27 10:00:00']);

        $this->actingAs($user)->put(route('review.update', '2026-10-26'), [
            'changed_case_file_id' => $case->id, 'abstraction' => ' 急ぐと見落とす ', 'concrete' => '朝の忘れ物',
        ])->assertRedirect(route('review.show', '2026-10-26'))->assertSessionHas('status');

        $this->actingAs($user)->put(route('review.update', '2026-10-26'), [
            'changed_case_file_id' => '', 'abstraction' => '前の晩に用意すると忘れない', 'concrete' => '',
        ])->assertRedirect();

        $this->assertSame(1, WeeklyReflection::count());
        $reflection = $user->weeklyReflections()->first();
        $this->assertSame('2026-10-26', substr((string) $reflection->week_start, 0, 10));
        $this->assertNull($reflection->changed_case_file_id);
        $this->assertSame('前の晩に用意すると忘れない', $reflection->abstraction);
        $this->assertNull($reflection->concrete);
    }

    /** 保存した週の問いは、その週の画面の入力欄に入って出ることを確かめる */
    public function test_saved_reflection_is_shown_in_the_form(): void
    {
        $user = User::factory()->create();
        WeeklyReflection::factory()->for($user)->create(['week_start' => '2026-10-19', 'abstraction' => '先週のつまり']);

        $this->actingAs($user)->get(route('review.show', '2026-10-19'))->assertOk()->assertSee('先週のつまり');
        $this->actingAs($user)->get(route('review.show'))->assertOk()->assertDontSee('先週のつまり');
    }

    /** 他人の事件を「考えが変わった事件」に選んで送ると、保存されずにエラーになることを確かめる */
    public function test_cannot_choose_someone_elses_case(): void
    {
        $user = User::factory()->create();
        $other = CaseFile::factory()->create(['created_at' => '2026-10-27 10:00:00']);

        $this->actingAs($user)->put(route('review.update', '2026-10-26'), ['changed_case_file_id' => $other->id])
            ->assertSessionHasErrors('changed_case_file_id');

        $this->assertSame(0, WeeklyReflection::count());
    }

    /** 他人のふりかえりは自分の画面に出ず、同じ週に保存しても他人の行は変わらないことを確かめる */
    public function test_reflections_are_per_user(): void
    {
        $user = User::factory()->create();
        $theirs = WeeklyReflection::factory()->create(['week_start' => '2026-10-26', 'abstraction' => '他人のつまり']);

        $this->actingAs($user)->get(route('review.show'))->assertOk()->assertDontSee('他人のつまり');
        $this->actingAs($user)->put(route('review.update', '2026-10-26'), ['abstraction' => '自分のつまり'])->assertRedirect();

        $this->assertSame('他人のつまり', $theirs->refresh()->abstraction);
        $this->assertSame(2, WeeklyReflection::count());
    }

    /** 選択肢は「その週に作った・見立ての状態を変えた」自分の事件で、動きのない古い事件と他人の事件は出ないことを確かめる */
    public function test_case_options_are_this_weeks_own_cases(): void
    {
        $user = User::factory()->create();
        CaseFile::factory()->for($user)->create(['title' => '今週作った事件', 'created_at' => '2026-10-26 09:00:00']);
        $changed = CaseFile::factory()->for($user)->create(['title' => '今週考えが変わった事件', 'created_at' => '2026-09-01 09:00:00']);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $changed->id]);
        HypothesisStatusLog::factory()->create(['hypothesis_id' => $hypothesis->id, 'created_at' => '2026-10-28 08:00:00']);
        CaseFile::factory()->for($user)->create(['title' => '動きのない古い事件', 'created_at' => '2026-09-01 09:00:00']);
        CaseFile::factory()->create(['title' => '他人の今週の事件', 'created_at' => '2026-10-27 09:00:00']);

        $this->actingAs($user)->get(route('review.show'))->assertOk()
            ->assertSee('今週作った事件')
            ->assertSee('今週考えが変わった事件')
            ->assertDontSee('動きのない古い事件')
            ->assertDontSee('他人の今週の事件');
    }

    /** 月曜日でない日付・未来の週・長すぎる文は保存されないことを確かめる */
    public function test_invalid_weeks_and_long_text_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/review/2026-10-28', ['abstraction' => 'x'])->assertNotFound();
        $this->actingAs($user)->put('/review/2026-11-02', ['abstraction' => 'x'])->assertNotFound();
        $this->actingAs($user)->put(route('review.update', '2026-10-26'), [
            'abstraction' => str_repeat('あ', config('suiri.limits.reflection_text') + 1),
        ])->assertSessionHasErrors('abstraction');

        $this->assertSame(0, WeeklyReflection::count());
    }

    /** 週の問いは1問ずつ進み（行があれば2問目、つまり？を書けば3問目、全部書けば「書けたね」）、?q= で選んだ問いを直せて、ほかの答えは hidden で送り直すことを確かめる */
    public function test_questions_go_one_by_one(): void
    {
        $user = User::factory()->create();
        $q = config('suiri.review.questions');
        $reflection = WeeklyReflection::factory()->for($user)->create(['week_start' => '2026-10-26', 'abstraction' => null, 'concrete' => null]);

        $this->actingAs($user)->get(route('review.show'))->assertOk()
            ->assertSee($q['abstraction']['label'])->assertSee('ステップ 2 / 3')->assertDontSee($q['concrete']['label']);

        $reflection->update(['abstraction' => '急ぐと見落とす']);
        $this->actingAs($user)->get(route('review.show'))->assertOk()
            ->assertSee($q['concrete']['label'])->assertSee('name="abstraction" value="急ぐと見落とす"', false);

        $reflection->update(['concrete' => '朝の忘れ物']);
        $this->actingAs($user)->get(route('review.show'))->assertOk()
            ->assertSee($q['done'])->assertSee('急ぐと見落とす')->assertSee('朝の忘れ物')->assertDontSee('ステップ');

        $this->actingAs($user)->get(route('review.show', ['week' => '2026-10-26', 'q' => 'abstraction']))->assertOk()
            ->assertSee($q['abstraction']['label'])->assertSee('name="concrete" value="朝の忘れ物"', false);
    }
}
