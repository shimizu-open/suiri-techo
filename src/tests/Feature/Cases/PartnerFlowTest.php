<?php

namespace Tests\Feature\Cases;

use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\Hypothesis;
use App\Models\User;
use App\Queries\Cases\CaseBoardQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/**
 * 相棒の渡し合いの部品（<x-case-partner.flow>・C-11）：付箋 → 書く → ○ を付けて渡す → 自分の番 → いっしょに見せ合う → 見せ合い。
 * 見せ合う前はもう片方の見立ての文を HTML に入れないこと、送り先（見立て・○×・見せ合う）が今の受付のままであることを確かめる
 */
class PartnerFlowTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    private User $user;

    private CaseFile $case;

    /** 相棒の名前（長男）と表示名（父）のある人の、手がかり2つの相棒モードの事件を用意する */
    protected function setUp(): void
    {
        parent::setUp();
        View::share('errors', new ViewErrorBag);
        $this->user = $this->member(['name' => '父', 'partner_name' => '長男']);
        $this->case = $this->caseOf($this->user, ['partner_mode' => true, 'scene' => 'family', 'title' => '体操着をなぜ忘れる？']);
        Clue::factory()->create(['case_file_id' => $this->case->id, 'body' => '玄関に袋がなかった']);
        Clue::factory()->create(['case_file_id' => $this->case->id, 'body' => '朝7時に起きた']);
    }

    /** 相棒の番でまだ書いていなければ、付箋と、相棒の見立てとして送る書く欄が出て、自分の見立ては HTML に入らないことを確かめる */
    public function test_partner_turn_starts_with_the_handoff_and_hides_the_other_side(): void
    {
        $this->hypothesis('self', '父の考え');

        $html = $this->flow('partner');

        $this->assertMatchesRegularExpression('/長男さんの番だよ。<\/span>\s*<span class="block">父さんは見ないでね/', $html);
        $this->assertStringContainsString('href="#relay-partner-write"', $html);
        $this->assertStringContainsString('action="'.route('hypotheses.store', $this->case).'"', $html);
        $this->assertStringContainsString('name="author" value="partner"', $html);
        $this->assertStringContainsString('どうしてだと思う？', $html);
        $this->assertStringContainsString('玄関に袋がなかった', $html);
        $this->assertStringNotContainsString('父の考え', $html);
    }

    /** 相棒が書いたあとは、○× のボタン（今の ○× の受付へ送る）と「父さんにわたしてね」のリンクが出ることを確かめる */
    public function test_after_writing_the_partner_marks_clues_and_passes_the_phone(): void
    {
        $theirs = $this->hypothesis('partner', '長男の考え');
        $this->hypothesis('self', '父の考え');
        $clue = $this->case->clues()->first();

        $html = $this->flow('partner');

        $this->assertStringContainsString('合う手がかりに ○ を付けよう', $html);
        $this->assertStringContainsString('長男の考え', $html);
        $this->assertStringContainsString('action="'.route('fits.update', [$theirs, $clue]).'"', $html);
        $this->assertStringContainsString('書けたら、父さんにわたしてね', $html);
        $this->assertStringContainsString('href="'.route('cases.show', ['case' => $this->case, 'turn' => 'self']).'"', $html);
        $this->assertStringNotContainsString('長男さんの番だよ', $html);
        $this->assertStringNotContainsString('父の考え', $html);
    }

    /** 自分の番では、書いたあとに「いっしょに見せ合おう！」（見せ合う受付へ送る）が出て、相棒の見立ては HTML に入らないことを確かめる */
    public function test_self_turn_leads_to_reveal_without_showing_the_partner(): void
    {
        $this->hypothesis('partner', '長男の考え');
        $html = $this->flow('self');
        $this->assertMatchesRegularExpression('/父さんの番だよ。<\/span>\s*<span class="block">長男さんは見ないでね/', $html);

        $this->hypothesis('self', '父の考え');
        $html = $this->flow('self');

        $this->assertStringContainsString('書けたら、長男さんをよぼう', $html);
        $this->assertStringContainsString('2人とも書けたね！', $html);
        $this->assertStringContainsString('action="'.route('cases.reveal', $this->case).'"', $html);
        $this->assertStringContainsString('いっしょに見せ合おう！', $html);
        $this->assertStringContainsString('父の考え', $html);
        $this->assertStringNotContainsString('長男の考え', $html);
    }

    /** 書いた文を送り直したとき（エラー）は、付箋を出さずに書く欄を初めから開くことを確かめる */
    public function test_a_resend_opens_the_write_card_directly(): void
    {
        $this->app['session.store']->put('_old_input', ['form_key' => 'partner-hypothesis', 'body' => '']);
        request()->setLaravelSession($this->app['session.store']);

        $html = $this->flow('partner');

        $this->assertStringNotContainsString('長男さんの番だよ', $html);
        $this->assertDoesNotMatchRegularExpression('/id="relay-partner-write" class="[^"]*\bhidden\b/', $html);
    }

    /** 見せ合ったあとは、2人の見立てが並び、○ の付け方が分かれた荷札だけに「見方が分かれたよ」が出ることを確かめる */
    public function test_reveal_lines_up_both_sides_and_marks_the_split_clue(): void
    {
        $mine = $this->hypothesis('self', '父の考え');
        $theirs = $this->hypothesis('partner', '長男の考え');
        [$shared, $split] = $this->case->clues()->orderBy('id')->get()->all();
        // 2人とも1つ目の手がかりに ○、2つ目は長男だけが ○ を付ける
        foreach ([[$mine, $shared], [$theirs, $shared], [$theirs, $split]] as [$h, $c]) {
            $this->actingAs($this->user)->put(route('fits.update', [$h, $c]), ['fit' => 'fits']);
        }
        $this->case->update(['revealed_at' => now()]);

        $html = $this->flow(null);

        $this->assertStringContainsString('2人の見立てを並べたよ', $html);
        $this->assertStringContainsString('見ていたところの違いを話してみよう', $html);
        $this->assertMatchesRegularExpression('/長男さんの見立て.*長男の考え.*父さんの見立て.*父の考え/s', $html);
        $this->assertSame(1, substr_count($html, '見方が分かれたよ'));
        $this->assertStringContainsString('見方が分かれた手がかり 1つ', $html);
        $this->assertStringNotContainsString('勝ち', $html);
    }

    /** 子ども向けの言葉の人には「見立て」を「かも」と言い、相棒モードでない事件では何も出さないことを確かめる */
    public function test_kid_words_and_nothing_outside_partner_mode(): void
    {
        $this->hypothesis('partner', '長男の考え');
        $this->assertStringContainsString('長男さんの「かも」', $this->flow('partner', kid: true));

        $this->case->update(['partner_mode' => false]);
        $this->assertSame('', trim($this->flow(null)));
    }

    /** 事件の画面と同じ値（CaseBoardQuery）で部品を描き、HTML を返す */
    private function flow(?string $turn, bool $kid = false): string
    {
        $board = app(CaseBoardQuery::class)->handle($this->case->fresh(), $this->user, $turn, null, null);

        return Blade::render(
            '<x-case-partner.flow :case="$case" :board="$board" :partner-name="$partnerName" :turn="$turn" :self-name="$selfName" :kid="$kid" />',
            ['case' => $board['case'], 'board' => $board['board'], 'partnerName' => $board['partnerName'], 'turn' => $board['turn'],
                'selfName' => $this->user->name, 'kid' => $kid],
        );
    }

    /** 書いた人（self・partner）と文を決めて、この事件に見立てを1つ足す */
    private function hypothesis(string $author, string $body): Hypothesis
    {
        return Hypothesis::factory()->create(['case_file_id' => $this->case->id, 'author' => $author, 'body' => $body]);
    }
}
