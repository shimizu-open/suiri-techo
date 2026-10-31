<?php

namespace Tests\Feature\Cases;

use App\Models\Clue;
use App\Models\Hypothesis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 相棒モード（C-11）：相棒の番 → 自分の番 → 見せ合う。見せ合うまで互いの見立てを隠し、見せ合ったら ○ の違いも並べる */
class PartnerModeTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 相棒の名前がある人だけ相棒モードで作れ、相棒モードでは最初の見立てを受け取らないことを確かめる */
    public function test_partner_mode_needs_a_partner_name(): void
    {
        $solo = $this->member(['partner_name' => null]);
        $this->actingAs($solo)->get(route('cases.create'))->assertDontSee('相棒モード');
        $this->actingAs($solo)->post(route('cases.store'), ['title' => 'なぞ', 'scene' => 'family', 'partner_mode' => '1']);
        $this->assertFalse($solo->caseFiles()->first()->partner_mode);

        $parent = $this->member(['partner_name' => '長男']);
        $this->actingAs($parent)->get(route('cases.create'))
            ->assertSee('長男といっしょに考える？')->assertSee('相棒モード')->assertSee('name="partner_mode"', false);
        $this->actingAs($parent)->post(route('cases.store'), [
            'title' => 'なぞ', 'scene' => 'family', 'partner_mode' => '1', 'hypothesis_body' => '先に書いた見立て',
        ]);
        $case = $parent->caseFiles()->first();
        $this->assertTrue($case->partner_mode);
        $this->assertSame(0, $case->hypotheses()->count());
    }

    /** 見せ合う前は、相棒の番では自分の見立てが、自分の番では相棒の見立てが（数も）隠れ、予想も立てられないことを確かめる */
    public function test_each_turn_hides_the_other_side(): void
    {
        $user = $this->member(['name' => '父', 'partner_name' => '長男']);
        $case = $this->caseOf($user, ['partner_mode' => true, 'scene' => 'family']);

        $this->actingAs($user)->post(route('hypotheses.store', $case), ['body' => '相棒の考え', 'author' => 'partner'])
            ->assertRedirect(route('cases.show', ['case' => $case, 'turn' => 'partner']).'#hypotheses');
        $this->actingAs($user)->post(route('hypotheses.store', $case), ['body' => '自分の考え', 'author' => 'self']);

        $this->actingAs($user)->get(route('cases.show', $case))->assertSee('書けたら、父さんにわたしてね')
            ->assertSee('相棒の考え')->assertDontSee('自分の考え')->assertDontSee('かくれているよ');
        $this->actingAs($user)->get(route('cases.show', ['case' => $case, 'turn' => 'self']))->assertSee('書けたら、長男さんをよぼう')
            ->assertSee('自分の考え')->assertDontSee('相棒の考え')->assertSee('いっしょに見せ合おう！');

        $hypothesis = Hypothesis::where('body', '自分の考え')->first();
        $this->actingAs($user)->post(route('predictions.store', $hypothesis), [
            'statement' => 's', 'confidence' => 70, 'check_on' => today()->toDateString(),
        ])->assertForbidden();
    }

    /** 見せ合うと両方の見立てが並び、手がかりごとに ○ の付け方の違いが「見方が分かれたよ」として出ることを確かめる */
    public function test_reveal_shows_both_sides_and_differences(): void
    {
        $user = $this->member(['name' => '父', 'partner_name' => '長男']);
        $case = $this->caseOf($user, ['partner_mode' => true]);
        $shared = Clue::factory()->create(['case_file_id' => $case->id, 'body' => '袋が玄関になかった']);
        $split = Clue::factory()->create(['case_file_id' => $case->id, 'body' => '朝7時に起きた']);
        $mine = Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => '自分の考え']);
        $theirs = Hypothesis::factory()->byPartner()->create(['case_file_id' => $case->id, 'body' => '相棒の考え']);
        // 両方とも1つ目の手がかりに ○、2つ目は相棒だけが ○ を付ける
        foreach ([[$mine, $shared], [$theirs, $shared], [$theirs, $split]] as [$h, $c]) {
            $this->actingAs($user)->put(route('fits.update', [$h, $c]), ['fit' => 'fits']);
        }

        $this->actingAs($user)->post(route('cases.reveal', $case))->assertRedirect(route('cases.show', $case).'#reveal');

        $this->assertNotNull($case->refresh()->revealed_at);
        $board = $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSeeInOrder(['見せ合い（ちがい 1つ）', '長男さんの見立て', '相棒の考え', '父さんの見立て', '自分の考え'])
            ->assertSee('見方が分かれた手がかり 1つ');
        $this->assertSame(1, substr_count($board->getContent(), '見方が分かれたよ'));
    }

    /** 相棒モードでない事件では、書いた人に「相棒」を送っても自分の見立てになることを確かめる */
    public function test_author_is_self_outside_partner_mode(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->post(route('hypotheses.store', $case), ['body' => '考え', 'author' => 'partner']);

        $this->assertSame('self', $case->hypotheses()->latest('id')->first()->author->value);
    }
}
