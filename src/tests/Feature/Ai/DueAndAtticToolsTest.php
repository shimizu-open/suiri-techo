<?php

namespace Tests\Feature\Ai;

use App\Mcp\Servers\SuiriServer;
use App\Mcp\Tools\ListDuePredictions;
use App\Mcp\Tools\SearchAttic;
use App\Models\AtticNote;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 道具 list_due_predictions・search_attic：本人の分だけ・仕事の場面から来たものは出ない */
class DueAndAtticToolsTest extends TestCase
{
    use RefreshDatabase;

    /** その人のその場面の事件に、確かめる日が $checkOn の答え合わせ待ちの予想を作る */
    private function prediction(User $user, string $statement, string $checkOn, string $scene = 'daily'): Prediction
    {
        $case = CaseFile::factory()->for($user)->scene($scene)->create();

        return Prediction::factory()->create([
            'hypothesis_id' => Hypothesis::factory()->create(['case_file_id' => $case->id])->id,
            'statement' => $statement,
            'check_on' => $checkOn,
        ]);
    }

    /** 確かめる日が来た本人の予想だけが出る。仕事の場面・他人・まだ先・答え合わせ済み・見せ合う前は出ない */
    public function test_due_predictions_are_own_visible_and_pending(): void
    {
        $user = User::factory()->withPartner()->create();
        $today = today()->toDateString();
        $this->prediction($user, '今日確かめる予想', $today);
        $this->prediction($user, '仕事の予想', $today, 'work');
        $this->prediction(User::factory()->create(), '他人の予想', $today);
        $this->prediction($user, '来週の予想', today()->addDays(7)->toDateString());
        $this->prediction($user, '済んだ予想', $today)->update(['result' => 'hit', 'resolved_at' => now()]);
        $hidden = $this->prediction($user, '見せ合う前の予想', $today);
        $hidden->caseFile->update(['partner_mode' => true, 'revealed_at' => null]);

        SuiriServer::actingAs($user)->tool(ListDuePredictions::class)
            ->assertOk()
            ->assertSee('今日確かめる予想')
            ->assertDontSee(['仕事の予想', '他人の予想', '来週の予想', '済んだ予想', '見せ合う前の予想']);

        SuiriServer::actingAs($user)->tool(ListDuePredictions::class, ['days_ahead' => 7])->assertSee('来週の予想');
        SuiriServer::actingAs($user)->tool(ListDuePredictions::class, ['days_ahead' => 99])->assertHasErrors();
    }

    /** 屋根裏の棚：手で足した知識と、見える事件の知識が出る。仕事の場面の事件の知識と他人の知識は出ない。言葉で絞れる */
    public function test_attic_shows_own_notes_except_work(): void
    {
        $user = User::factory()->create();
        AtticNote::factory()->for($user)->create(['rule' => '手で足した知識']);
        AtticNote::factory()->for($user)->create(['rule' => '日常の知識', 'case_file_id' => CaseFile::factory()->for($user)->create()->id]);
        AtticNote::factory()->for($user)->create(['rule' => '仕事の知識', 'case_file_id' => CaseFile::factory()->for($user)->scene('work')->create()->id]);
        AtticNote::factory()->create(['rule' => '他人の知識']);

        SuiriServer::actingAs($user)->tool(SearchAttic::class)
            ->assertOk()
            ->assertSee(['手で足した知識', '日常の知識'])
            ->assertDontSee(['仕事の知識', '他人の知識']);

        SuiriServer::actingAs($user)->tool(SearchAttic::class, ['keyword' => '日常'])->assertSee('日常の知識')->assertDontSee('手で足した知識');
    }
}
