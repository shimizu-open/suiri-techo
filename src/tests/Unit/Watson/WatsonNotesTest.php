<?php

namespace Tests\Unit\Watson;

use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\DismissedHint;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Services\Watson\WatsonNotes;
use Tests\TestCase;

/** ワトソンのひとことの規則（W-1〜W-6）と、優先度・閉じた規則の扱い。表を使わず、メモリの中の事件で確かめる */
class WatsonNotesTest extends TestCase
{
    /** 答え合わせ待ちの予想が1つある形（W-6 を出さないために使う） */
    private const PENDING = [['statement' => '袋が玄関にあるはず', 'confidence' => 70, 'check_on' => '2026-11-02']];

    /** メモリの中に事件を組み立てる（手がかり・見立て・予想・閉じた規則を渡せる） */
    private function caseWith(array $clues = [], array $hypotheses = [], array $predictions = [], string $scene = 'daily', array $dismissed = []): CaseFile
    {
        $case = new CaseFile(['title' => '体操着をなぜ忘れる？', 'scene' => $scene, 'status' => 'open']);
        $case->setRelation('clues', collect($clues)->map(fn ($c) => new Clue(['kind' => $c[0], 'body' => $c[1]])));
        $case->setRelation('hypotheses', collect($hypotheses)->map(fn ($h) => new Hypothesis(['body' => $h, 'status' => 'considering'])));
        $case->setRelation('predictions', collect($predictions)->map(fn ($p) => new Prediction($p)));
        $case->setRelation('dismissedHints', collect($dismissed)->map(fn ($r) => new DismissedHint(['rule' => $r])));

        return $case;
    }

    /** ひとことの規則のキーだけを返す（なければ null） */
    private function ruleOf(CaseFile $case, ?string $alert = null): ?string
    {
        return app(WatsonNotes::class)->forCase($case, $alert)['rule'] ?? null;
    }

    /** W-1：「見た」の手がかりの推測の言葉に当たり、どの手がかりかを引用する。「聞いた」の手がかりでは出ないことを確かめる */
    public function test_w1_guess_in_seen_clue(): void
    {
        $note = app(WatsonNotes::class)->forCase($this->caseWith([['seen', '寝坊したと思う']], ['a', 'b']));
        $this->assertSame('seen_guess', $note['rule']);
        $this->assertSame('寝坊したと思う', $note['quote']);

        $this->assertNull($this->ruleOf($this->caseWith([['heard', '寝坊したと思う、と言った']], ['a', 'b'], self::PENDING)));
    }

    /** W-2：「見た」の手がかりの原因の言葉に当たり、場所の「から」には当たらないことを確かめる */
    public function test_w2_cause_in_seen_clue(): void
    {
        $this->assertSame('seen_cause', $this->ruleOf($this->caseWith([['seen', '雨が降ったので遅れた']], ['a', 'b'])));
        $this->assertNull($this->ruleOf($this->caseWith([['seen', '駅から歩いてきた']], ['a', 'b'], self::PENDING)));
    }

    /** W-3：見立てが1つだけのときに出て、2つあれば出ないことを確かめる */
    public function test_w3_single_hypothesis(): void
    {
        $this->assertSame('single_hypothesis', $this->ruleOf($this->caseWith([], ['時間割を見ていない'], self::PENDING)));
        $this->assertNull($this->ruleOf($this->caseWith([], ['時間割を見ていない', '朝に急いでいる'], self::PENDING)));
    }

    /** W-4：仕事の場面の「よくなった」に当たり、日常の場面では同じ文でも出ないことを確かめる */
    public function test_w4_feels_better_only_in_work_scene(): void
    {
        $clues = [['heard', '前回より肩がよくなったと言った']];

        $this->assertSame('work_better', $this->ruleOf($this->caseWith($clues, ['a', 'b'], [], 'work')));
        $this->assertSame('no_check_date', $this->ruleOf($this->caseWith($clues, ['a', 'b'], [], 'daily')));
    }

    /** W-5：電話番号・メール・名前らしきものに当たり、ほかのどの規則よりも先に出ることを確かめる */
    public function test_w5_personal_info_comes_first(): void
    {
        $case = $this->caseWith([['seen', '山田さんが遅れたと思う']], ['a']);
        $this->assertSame('personal_info', $this->ruleOf($case));

        $this->assertSame('personal_info', app(WatsonNotes::class)->alertFor('ふつうの文', 'taro@example.com'));
        $this->assertNull(app(WatsonNotes::class)->alertFor('同僚Aさんが遅れた', null));
    }

    /** W-6：見立てはあるのに確かめる日のある予想（答え合わせ待ち）がないときに出て、あれば出ないことを確かめる */
    public function test_w6_no_check_date(): void
    {
        $this->assertSame('no_check_date', $this->ruleOf($this->caseWith([], ['a', 'b'])));

        $resolved = [['statement' => 's', 'confidence' => 70, 'check_on' => '2026-11-02', 'result' => 'hit']];
        $this->assertSame('no_check_date', $this->ruleOf($this->caseWith([], ['a', 'b'], $resolved)));

        $this->assertNull($this->ruleOf($this->caseWith([], ['a', 'b'], self::PENDING)));
        $this->assertNull($this->ruleOf($this->caseWith()), '見立てがなければ聞かない');
    }

    /** 閉じた規則は出さず、次に優先度の高い規則が出る。保存した直後の警告は、閉じていても出ることを確かめる */
    public function test_dismissed_rules_are_skipped_but_alerts_show(): void
    {
        $case = $this->caseWith([['seen', '寝坊したと思う']], ['a'], [], 'daily', ['seen_guess']);
        $this->assertSame('single_hypothesis', $this->ruleOf($case));

        $quiet = $this->caseWith([], ['a', 'b'], self::PENDING, 'daily', ['personal_info']);
        $note = app(WatsonNotes::class)->forCase($quiet, 'personal_info');
        $this->assertSame('personal_info', $note['rule']);
        $this->assertTrue($note['alert']);
    }

    /** 飛ばすように頼んだ規則（いまの一手と同じことを聞くもの）は出さないことを確かめる */
    public function test_skipped_rules_are_not_shown(): void
    {
        $case = $this->caseWith([], ['a', 'b']);

        $this->assertNull(app(WatsonNotes::class)->forCase($case, null, ['no_check_date']));
    }

    /** 閉じた事件では、見立て1つ・確かめる日なしの問いかけを出さないことを確かめる */
    public function test_closed_case_gets_no_nudges(): void
    {
        $case = $this->caseWith([], ['a']);
        $case->status = 'solved';

        $this->assertNull($this->ruleOf($case));
    }
}
