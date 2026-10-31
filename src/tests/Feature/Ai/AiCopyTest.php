<?php

namespace Tests\Feature\Ai;

use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;
use App\Support\Ai\ConsultText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 事件の画面の「AI に相談する用に写す」：写す文の中身と、仕事の場面では出さないこと */
class AiCopyTest extends TestCase
{
    use RefreshDatabase;

    /** 写す文に、なぞ・番号つきの手がかり・見立てと合う手がかり・予想・頼み方の文が入る */
    public function test_text_has_case_contents_and_request(): void
    {
        $case = CaseFile::factory()->create(['title' => '体操着をなぜ忘れる？']);
        $clue = Clue::factory()->create(['case_file_id' => $case->id, 'kind' => 'absent', 'body' => '玄関に袋がなかった']);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => '時間割を見ていない']);
        ClueHypothesis::factory()->create(['hypothesis_id' => $hypothesis->id, 'clue_id' => $clue->id]);
        Prediction::factory()->create(['hypothesis_id' => $hypothesis->id, 'statement' => '前の晩に見れば忘れない', 'confidence' => 70]);

        $text = ConsultText::for($case->fresh());

        $this->assertStringContainsString("■ なぞ\n体操着をなぜ忘れる？", $text);
        $this->assertStringContainsString('1. ［なかった］玄関に袋がなかった', $text);
        $this->assertStringContainsString('・時間割を見ていない（検討中）', $text);
        $this->assertStringContainsString('合う手がかり：1', $text);
        $this->assertStringContainsString('もしそうなら：前の晩に見れば忘れない（確かさ 70%', $text);
        $this->assertStringContainsString('［答え合わせ待ち］', $text);
        $this->assertStringContainsString('正誤は判定せず、ほかの見立てと、見立てを見分ける確かめ方の案を出してください。', $text);
        $this->assertStringContainsString('名前などの個人情報は書かないでください。', $text);
    }

    /** 相棒モードで見せ合う前は、見立てを写さない */
    public function test_text_hides_hypotheses_before_reveal(): void
    {
        $case = CaseFile::factory()->for(User::factory()->withPartner())->partner()->create();
        Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => '自分の見立て']);

        $text = ConsultText::for($case->fresh());

        $this->assertStringNotContainsString('自分の見立て', $text);
        $this->assertStringContainsString(config('ai_connect.copy.hidden'), $text);
    }

    /** 日常の事件ではボタンと写す文が出て、仕事の場面の事件では何も出ない */
    public function test_button_is_hidden_for_work_cases(): void
    {
        $daily = CaseFile::factory()->create(['title' => '日常のなぞ']);
        $work = CaseFile::factory()->scene('work')->create(['title' => '仕事のなぞ']);

        $this->blade('<x-case.ai-copy :case="$case" :board="null" />', ['case' => $daily])
            ->assertSee('AI に相談する用に写す')
            ->assertSee('日常のなぞ')
            ->assertSee('写したよ。ChatGPT に貼ってね');

        $html = (string) $this->blade('<x-case.ai-copy :case="$case" :board="null" />', ['case' => $work]);
        $this->assertSame('', trim($html));
    }
}
