<?php

namespace Tests\Feature\Ai;

use App\Mcp\Servers\SuiriServer;
use App\Mcp\Tools\GetCase;
use App\Mcp\Tools\ListCases;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 道具 list_cases・get_case：本人の事件だけ・仕事の場面は見えない・相棒モードで見せ合う前は見立てを隠す */
class ReadToolsTest extends TestCase
{
    use RefreshDatabase;

    /** 一覧には本人の日常・親子の事件だけが出る。仕事の場面の事件と他人の事件は出ない */
    public function test_list_cases_shows_only_own_non_work_cases(): void
    {
        $user = User::factory()->create();
        CaseFile::factory()->for($user)->create(['title' => '日常のなぞ']);
        CaseFile::factory()->for($user)->scene('family')->create(['title' => '親子のなぞ']);
        CaseFile::factory()->for($user)->scene('work')->create(['title' => '仕事のなぞ']);
        CaseFile::factory()->create(['title' => '他人のなぞ']);

        SuiriServer::actingAs($user)->tool(ListCases::class)
            ->assertOk()
            ->assertSee(['日常のなぞ', '親子のなぞ'])
            ->assertDontSee(['仕事のなぞ', '他人のなぞ']);
    }

    /** 状態と題名の言葉で絞れる（初めは捜査中だけ） */
    public function test_list_cases_filters_by_status_and_keyword(): void
    {
        $user = User::factory()->create();
        CaseFile::factory()->for($user)->create(['title' => '体操着をなぜ忘れる？']);
        CaseFile::factory()->for($user)->create(['title' => '朝の電車はなぜ混む？']);
        CaseFile::factory()->for($user)->solved()->create(['title' => '植木の葉はなぜ黄色い？']);

        SuiriServer::actingAs($user)->tool(ListCases::class)->assertSee('体操着')->assertDontSee('植木');
        SuiriServer::actingAs($user)->tool(ListCases::class, ['status' => 'solved'])->assertSee('植木')->assertDontSee('体操着');
        SuiriServer::actingAs($user)->tool(ListCases::class, ['keyword' => '電車'])->assertSee('電車')->assertDontSee('体操着');
        SuiriServer::actingAs($user)->tool(ListCases::class, ['status' => 'closed'])->assertHasErrors();
    }

    /** 事件の中身に、なぞ・手がかり・見立て（合う手がかりの番号）・予想と答え合わせの状態が入る */
    public function test_get_case_returns_clues_hypotheses_and_predictions(): void
    {
        $user = User::factory()->create();
        $case = CaseFile::factory()->for($user)->create(['title' => '体操着をなぜ忘れる？']);
        $clue = Clue::factory()->create(['case_file_id' => $case->id, 'kind' => 'absent', 'body' => '玄関に袋がなかった']);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => '時間割を見ていない']);
        ClueHypothesis::factory()->create(['hypothesis_id' => $hypothesis->id, 'clue_id' => $clue->id]);
        Prediction::factory()->create(['hypothesis_id' => $hypothesis->id, 'statement' => '前の晩に見れば忘れない', 'confidence' => 70]);

        SuiriServer::actingAs($user)->tool(GetCase::class, ['case_id' => $case->id])
            ->assertOk()
            ->assertSee(['体操着をなぜ忘れる？', '玄関に袋がなかった', 'なかった', '時間割を見ていない', '前の晩に見れば忘れない', '答え合わせ待ち'])
            ->assertStructuredContent(function ($json) use ($clue) {
                $json->where('hypotheses.0.fits', [$clue->id])->where('hypotheses.0.predictions.0.confidence', 70)->etc();
            });
    }

    /** 仕事の場面の事件・他人の事件・ない番号は、どれも同じ「見つかりません」の文（あるかどうかを漏らさない） */
    public function test_get_case_hides_work_others_and_missing_with_same_message(): void
    {
        $user = User::factory()->create();
        $work = CaseFile::factory()->for($user)->scene('work')->create(['title' => '仕事のなぞ']);
        $others = CaseFile::factory()->create(['title' => '他人のなぞ']);
        $message = config('ai_connect.messages.case_not_found');

        // 3つの見えない番号を1つずつ試し、どれも同じ失敗の文で、題名が出ないことを確かめる
        foreach ([$work->id, $others->id, 999999] as $id) {
            SuiriServer::actingAs($user)->tool(GetCase::class, ['case_id' => $id])
                ->assertHasErrors([$message])
                ->assertDontSee(['仕事のなぞ', '他人のなぞ']);
        }
    }

    /** 相棒モードで見せ合う前の事件は、見立てと予想を隠し、隠していることを伝える。見せ合ったあとは見える */
    public function test_get_case_hides_hypotheses_before_reveal(): void
    {
        $user = User::factory()->withPartner()->create();
        $case = CaseFile::factory()->for($user)->partner()->create();
        Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => '自分の見立て']);
        Hypothesis::factory()->byPartner()->create(['case_file_id' => $case->id, 'body' => '相棒の見立て']);

        SuiriServer::actingAs($user)->tool(GetCase::class, ['case_id' => $case->id])
            ->assertOk()
            ->assertSee(config('ai_connect.messages.hidden_note'))
            ->assertDontSee(['自分の見立て', '相棒の見立て']);

        $case->update(['revealed_at' => now()]);
        SuiriServer::actingAs($user)->tool(GetCase::class, ['case_id' => $case->id])->assertSee(['自分の見立て', '相棒の見立て']);
    }
}
