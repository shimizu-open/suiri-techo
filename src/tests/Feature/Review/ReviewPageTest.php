<?php

namespace Tests\Feature\Review;

use App\Models\AtticNote;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 週のふりかえりの画面（GET /review/{week?}）：週の住所・前後の週・数字・他人の記録が出ないこと・使わない言葉 */
class ReviewPageTest extends TestCase
{
    use RefreshDatabase;

    /** 今日を 2026-10-28（水）にする。今週は 2026-10-26 */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-28 12:00:00');
    }

    /** 週を省くと今週の画面になり、前の週へのリンクがあって、次の週へのリンクはないことを確かめる */
    public function test_current_week_is_the_default(): void
    {
        $this->actingAs(User::factory()->create())->get(route('review.show'))->assertOk()
            ->assertSee('10月26日〜11月1日')
            ->assertSee('今週')
            ->assertSee(route('review.show', '2026-10-19'), false)
            ->assertDontSee(route('review.show', '2026-11-02'), false);
    }

    /** 前の週を開くと、その週の期間と、次の週（今週）へのリンクが出ることを確かめる */
    public function test_previous_week_links_forward(): void
    {
        $this->actingAs(User::factory()->create())->get(route('review.show', '2026-10-19'))->assertOk()
            ->assertSee('10月19日〜10月25日')
            ->assertSee(route('review.show', '2026-10-26'), false);
    }

    /** 月曜日でない日付はその週の月曜日の住所へ送り直し、ない日付と未来の週は 404 になることを確かめる */
    public function test_week_parameter_is_normalized_or_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/review/2026-10-29')->assertRedirect(route('review.show', '2026-10-26'));
        $this->actingAs($user)->get('/review/2026-10-29?range=week')->assertRedirect(route('review.show', ['week' => '2026-10-26', 'range' => 'week']));
        $this->actingAs($user)->get('/review/2026-02-31')->assertNotFound();
        $this->actingAs($user)->get('/review/2026-11-02')->assertNotFound();
        $this->actingAs($user)->get('/review/26-10-26')->assertNotFound();
    }

    /** 自分の数字と知識は出て、他人の事件・知識は数字にも一覧にも出ないことを確かめる */
    public function test_only_own_records_are_shown(): void
    {
        $user = User::factory()->create();
        CaseFile::factory()->for($user)->create(['title' => '自分の今週の事件', 'created_at' => '2026-10-27 10:00:00']);
        AtticNote::factory()->for($user)->create(['rule' => '自分の今週の知識', 'created_at' => '2026-10-27 10:00:00']);
        CaseFile::factory()->count(3)->create(['title' => '他人の事件', 'created_at' => '2026-10-27 10:00:00']);
        AtticNote::factory()->create(['rule' => '他人の知識', 'created_at' => '2026-10-27 10:00:00']);

        $this->actingAs($user)->get(route('review.show'))->assertOk()
            ->assertSee('自分の今週の事件')
            ->assertSee('自分の今週の知識')
            ->assertDontSee('他人の事件')
            ->assertDontSee('他人の知識')
            ->assertSeeInOrder([config('suiri.review.numbers.cases.label'), '1', '件']);
    }

    /** 記録のない週は責めない一言だけが出て（0 の数は並べない）、連続記録・順位・ポイントの言葉はどこにも出ないことを確かめる */
    public function test_empty_week_is_calm_and_has_no_streaks(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('review.show'))->assertOk()
            ->assertSee(config('suiri.review.empty_week.title'))
            ->assertSee(config('suiri.review.empty_week.note'))
            ->assertDontSee(config('suiri.review.numbers.cases.label'));

        // 罰や比べ合いの言葉が1つも出ないことを確かめる（R-6）
        foreach (['連続', 'ランキング', 'ポイント', '順位'] as $word) {
            $response->assertDontSee($word);
        }
    }

    /** 記録のある週は、いちばん上に数の入った大きな一言（考えを変えた回数が先）と、3つの数が出ることを確かめる */
    public function test_headline_speaks_about_the_week(): void
    {
        $user = User::factory()->create();
        CaseFile::factory()->for($user)->count(2)->create(['created_at' => '2026-10-27 10:00:00']);

        $this->actingAs($user)->get(route('review.show'))->assertOk()
            ->assertSee(strtr(config('suiri.review.headline.cases.title'), [':n' => 2]))
            ->assertSeeInOrder(array_column(config('suiri.review.numbers'), 'label'));
        // 前の週を開いたときは「今週」を「この週」と読み替える
        $this->actingAs($user)->get(route('review.show', '2026-10-19'))->assertOk()
            ->assertSee(str_replace('今週', 'この週', config('suiri.review.empty_week.title')));
    }

    /** 週の問いは1問目（「なぜ」の補足つき）だけを出し、まだ先の問いは出さないこと・ノーベリ帳と屋根裏の棚への切り替えが出ることを確かめる */
    public function test_first_weekly_question_and_tabs_are_shown(): void
    {
        $q = config('suiri.review.questions');

        $this->actingAs(User::factory()->create())->get(route('review.show'))->assertOk()
            ->assertSee($q['changed_case']['label'])
            ->assertSee('ステップ 1 / 3')
            ->assertSee('なぜ')
            ->assertDontSee($q['abstraction']['label'])
            ->assertDontSee($q['concrete']['label'])
            ->assertSee(route('norbury.index'), false)
            ->assertSee(route('attic.index'), false);
    }

    /** ログインしていない人はふりかえりを開けず、トップへ送られることを確かめる */
    public function test_guests_are_redirected(): void
    {
        $this->get(route('review.show'))->assertRedirect(route('home'));
    }
}
