<?php

namespace Tests\Feature\Ai;

use App\Mcp\Servers\SuiriServer;
use App\Mcp\Tools\GetCase;
use App\Mcp\Tools\ListCases;
use App\Mcp\Tools\ListDuePredictions;
use App\Mcp\Tools\SearchAttic;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Feature\Ai\Concerns\ConnectsAi;
use Tests\TestCase;

/** 壊さない決まり：窓口の道具は読むだけの4つだけ。書き込み・答え合わせ・○×・状態・直す・消すの道具はない */
class ReadOnlyToolsTest extends TestCase
{
    use ConnectsAi, RefreshDatabase;

    /** 登録されている道具は、決めた4つだけ */
    public function test_only_four_read_tools_are_registered(): void
    {
        SuiriServer::tools()->assertRegistered([ListCases::class, GetCase::class, ListDuePredictions::class, SearchAttic::class]);

        $this->usePassportKeys();
        Passport::actingAs(User::factory()->create(), ['mcp:use']);
        $tools = collect($this->callMcp('tools/list')->assertOk()->json('result.tools'));

        $this->assertSame(['get_case', 'list_cases', 'list_due_predictions', 'search_attic'], $tools->pluck('name')->sort()->values()->all());
        // 道具を1つずつ見て、読むだけの印（readOnlyHint）があり、説明文に「正誤は判定せず」「予想の文と確かさは変えられない」が入るかを確かめる
        foreach ($tools as $tool) {
            $this->assertTrue($tool['annotations']['readOnlyHint'], $tool['name']);
            $this->assertFalse($tool['annotations']['openWorldHint'], $tool['name']);
            $this->assertStringContainsString('正誤は判定せず', $tool['description']);
            $this->assertStringContainsString('予想の文と確かさは、書いたあと変えられない決まり', $tool['description']);
            $this->assertDoesNotMatchRegularExpression('/add|create|update|delete|resolve|withdraw|fit|status/', $tool['name']);
        }
    }

    /** 道具を呼んでも、記録の表は何も変わらない（読むだけ） */
    public function test_tools_do_not_change_records(): void
    {
        $user = User::factory()->create();
        $case = CaseFile::factory()->for($user)->create();
        $before = $case->fresh()->toArray();

        SuiriServer::actingAs($user)->tool(ListCases::class)->assertOk();
        SuiriServer::actingAs($user)->tool(GetCase::class, ['case_id' => $case->id])->assertOk();
        SuiriServer::actingAs($user)->tool(ListDuePredictions::class)->assertOk();
        SuiriServer::actingAs($user)->tool(SearchAttic::class)->assertOk();

        $this->assertSame($before, $case->fresh()->toArray());
    }
}
