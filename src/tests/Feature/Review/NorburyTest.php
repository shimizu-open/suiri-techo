<?php

namespace Tests\Feature\Review;

use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ノーベリ帳（GET /norbury）：外れた予想の一覧と、見落としの種類ごとの数 */
class NorburyTest extends TestCase
{
    use RefreshDatabase;

    /** その人の事件に、結果を決めた予想を作る */
    private function predict(User $user, string $statement, ?string $result, ?string $reason = null, array $extra = []): Prediction
    {
        $case = CaseFile::factory()->for($user)->create(['title' => 'なぞ：'.$statement]);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id]);

        return Prediction::factory()->create([
            'hypothesis_id' => $hypothesis->id, 'statement' => $statement, 'result' => $result,
            'miss_reason' => $reason, 'resolved_at' => $result ? now() : null,
        ] + $extra);
    }

    /** 自分の外れた予想だけが、なぞ・予想の文・見落としの種類・一言と一緒に出て、当たりや他人の外れは出ないことを確かめる */
    public function test_lists_own_misses_only(): void
    {
        $user = User::factory()->create();
        $this->predict($user, '自分の外れ', 'miss', 'assumption', ['result_note' => '雨の日だった']);
        $this->predict($user, '自分の当たり', 'hit');
        $this->predict($user, '答え合わせ待ち', null);
        $this->predict(User::factory()->create(), '他人の外れ', 'miss', 'chance');

        $this->actingAs($user)->get(route('norbury.index'))->assertOk()
            ->assertSee('なぞ：自分の外れ')
            ->assertSee('「自分の外れ」', false)
            ->assertSee('思い込みがあった')
            ->assertSee('雨の日だった')
            ->assertDontSee('自分の当たり')
            ->assertDontSee('答え合わせ待ち')
            ->assertDontSee('他人の外れ');
    }

    /** 見落としの種類ごとの数を、自分の外れだけで数え、選ばなかった外れは別に数えることを確かめる */
    public function test_counts_by_miss_reason(): void
    {
        $user = User::factory()->create();
        // 見落としの種類を1つずつ外れにする（思い込み2件・選ばなかった1件）
        foreach (['assumption', 'assumption', null] as $i => $reason) {
            $this->predict($user, "外れ{$i}", 'miss', $reason);
        }
        $this->predict(User::factory()->create(), '他人の外れ', 'miss', 'assumption');

        $this->actingAs($user)->get(route('norbury.index'))->assertOk()
            ->assertSeeInOrder(['外れ', '3', '件'])
            ->assertSeeInOrder(['思い込みがあった', '2件'])
            ->assertSeeInOrder(['偶然', '0件'])
            ->assertSeeInOrder(['選ばなかった', '1件']);
    }

    /** 外れがないときは責めない案内と、『黄色い顔』の名前の由来が出ることを確かめる */
    public function test_empty_state_and_story(): void
    {
        $this->actingAs(User::factory()->create())->get(route('norbury.index'))->assertOk()
            ->assertSee(config('suiri.review.norbury.empty.title'))
            ->assertSee('黄色い顔')
            ->assertSee('ノーベリ')
            ->assertDontSee('まちがいノート');
    }

    /** ふだんの場面が親子の人には、子ども向けの呼び名「まちがいノート」と子ども向けの見落としの言葉が出ることを確かめる */
    public function test_family_scene_shows_kid_words(): void
    {
        $user = User::factory()->create(['default_scene' => 'family']);
        $this->predict($user, '外れ', 'miss', 'chance');

        $this->actingAs($user)->get(route('norbury.index'))->assertOk()
            ->assertSee('まちがいノート')
            ->assertSee('まちがいは宝もの')
            ->assertSee('たまたま');
    }
}
