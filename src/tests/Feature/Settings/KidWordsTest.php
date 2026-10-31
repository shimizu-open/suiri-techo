<?php

namespace Tests\Feature\Settings;

use App\Enums\Scene;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\User;
use App\Support\Guide\GuideLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 子ども向けの言葉（まちがいノート・ぼくの図鑑・「かも」・見に行く日など）を出す条件が1つにそろっていること：
 * ふだんの場面（設定）が「親子」の人に出す。事件ごとの場面では切り替えない。手引きは ?kid=0・?kid=1 で選んだ向きが先
 */
class KidWordsTest extends TestCase
{
    use RefreshDatabase;

    /** ふだんの場面が親子の人だけに、ノーベリ帳と屋根裏の棚で子ども向けの呼び名が出ることを確かめる */
    public function test_review_pages_use_kid_words_only_for_family_users(): void
    {
        $family = User::factory()->create(['default_scene' => Scene::Family]);
        $daily = User::factory()->create(['default_scene' => Scene::Daily]);

        $this->actingAs($family)->get(route('norbury.index'))->assertSee('まちがいノート');
        $this->actingAs($family)->get(route('attic.index'))->assertSee('ぼくの図鑑');
        $this->actingAs($daily)->get(route('norbury.index'))->assertDontSee('まちがいノート');
        $this->actingAs($daily)->get(route('attic.index'))->assertDontSee('ぼくの図鑑');
    }

    /** 事件の画面では、事件の場面ではなく、その人のふだんの場面で子ども向けの言葉（「かも」・見に行く）を決めることを確かめる */
    public function test_case_page_follows_the_users_default_scene(): void
    {
        $family = User::factory()->create(['default_scene' => Scene::Family]);
        $daily = User::factory()->create(['default_scene' => Scene::Daily]);
        $dailyCase = $this->caseWithHypothesis($family, Scene::Daily);
        $familyCase = $this->caseWithHypothesis($daily, Scene::Family);

        $predict = fn (CaseFile $case) => route('cases.show', ['case' => $case, 'open' => 'predict-'.$case->hypotheses()->first()->id]);
        $kidButton = config('suiri.case_board.kid.cards.predict.button');

        $this->actingAs($family)->get($predict($dailyCase))->assertSee($kidButton)->assertSee('「かも」');
        $this->actingAs($daily)->get($predict($familyCase))->assertDontSee($kidButton)->assertDontSee('「かも」');
    }

    /** 手引きは、選んでいなければふだんの場面で決まり、?kid=0・?kid=1 で選んだ向きが先になることを確かめる */
    public function test_guide_defaults_to_the_users_scene_and_the_toggle_wins(): void
    {
        $family = User::factory()->create(['default_scene' => Scene::Family]);
        $daily = User::factory()->create(['default_scene' => Scene::Daily]);
        $skill = 'S1';
        $kidBody = app(GuideLibrary::class)->skill($skill)['kid_body'];

        $this->actingAs($family)->get(route('guide.skills.show', $skill))->assertSee($kidBody);
        $this->actingAs($family)->get(route('guide.skills.show', [$skill, 'kid' => 0]))->assertDontSee($kidBody);
        $this->actingAs($daily)->get(route('guide.skills.show', $skill))->assertDontSee($kidBody);
        $this->actingAs($daily)->get(route('guide.skills.show', [$skill, 'kid' => 1]))->assertSee($kidBody);
    }

    /** その人の、見立てが1つある捜査中の事件を作る */
    private function caseWithHypothesis(User $user, Scene $scene): CaseFile
    {
        $case = CaseFile::factory()->for($user)->create(['scene' => $scene]);
        Hypothesis::factory()->create(['case_file_id' => $case->id]);

        return $case;
    }
}
