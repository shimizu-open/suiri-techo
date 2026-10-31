<?php

namespace Tests\Feature\Ai;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Feature\Ai\Concerns\ConnectsAi;
use Tests\TestCase;

/** 窓口（POST /mcp）の入口の守り：通行証なしは 401、範囲の足りない通行証は 403、よその画面からは 403 */
class McpAuthTest extends TestCase
{
    use ConnectsAi, RefreshDatabase;

    /** テストごとの準備：テスト用の鍵を入れる（窓口の入口が通行証を確かめる係を作るのに要る） */
    protected function setUp(): void
    {
        parent::setUp();
        $this->usePassportKeys();
    }

    /** 通行証なしは 401 で、案内の住所（resource_metadata）を WWW-Authenticate で知らせる（ログインの画面へ送らない） */
    public function test_request_without_token_gets_401_with_metadata(): void
    {
        $response = $this->callMcp('tools/list')->assertUnauthorized();

        $this->assertStringContainsString(
            'resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp').'"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    /** Accept を付けない要求（curl など）でも、302 ではなく 401 を返す */
    public function test_plain_request_without_token_also_gets_401(): void
    {
        $this->post('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
    }

    /** 窓口を読む範囲（mcp:use）の通行証なら、道具の一覧が返る */
    public function test_token_with_scope_can_list_tools(): void
    {
        Passport::actingAs(User::factory()->create(), ['mcp:use']);

        $names = collect($this->callMcp('tools/list')->assertOk()->json('result.tools'))->pluck('name')->sort()->values()->all();

        $this->assertSame(['get_case', 'list_cases', 'list_due_predictions', 'search_attic'], $names);
    }

    /** 範囲が mcp:use でない通行証は 403 */
    public function test_token_without_scope_is_forbidden(): void
    {
        Passport::actingAs(User::factory()->create(), ['other']);

        $this->callMcp('tools/list')->assertForbidden();
    }

    /** よその画面（Origin）からの呼び出しは 403。推理手帳自身と ChatGPT・Claude の Origin は通る */
    public function test_foreign_origin_is_rejected(): void
    {
        Passport::actingAs(User::factory()->create(), ['mcp:use']);

        $this->callMcp('tools/list', [], ['Origin' => 'https://evil.example'])->assertForbidden();
        $this->callMcp('tools/list', [], ['Origin' => 'https://chatgpt.com'])->assertOk();
        $this->callMcp('tools/list', [], ['Origin' => rtrim(url('/'), '/')])->assertOk();
    }

    /** AI が最初に読む窓口の説明（instructions）に、読むだけ・正誤を決めない・仕事の場面は見えない、が入っている */
    public function test_initialize_returns_instructions(): void
    {
        Passport::actingAs(User::factory()->create(), ['mcp:use']);

        $instructions = $this->callMcp('initialize', [
            'protocolVersion' => '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0'],
        ])->assertOk()->json('result.instructions');

        $this->assertSame(config('ai_connect.instructions'), $instructions);
        $this->assertStringContainsString('読むだけ', mb_substr($instructions, 0, 512));
        $this->assertStringContainsString('決めないでください', mb_substr($instructions, 0, 512));
        $this->assertStringContainsString('仕事の場面の事件は', $instructions);
    }
}
