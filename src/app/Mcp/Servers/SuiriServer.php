<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetCase;
use App\Mcp\Tools\ListCases;
use App\Mcp\Tools\ListDuePredictions;
use App\Mcp\Tools\SearchAttic;
use Laravel\Mcp\Server;

/**
 * 推理手帳の AI の窓口（POST /mcp。routes/ai.php）。ChatGPT・Claude が、本人の許可（通行証）を見せて記録を読みに来る。
 * 置く道具は読むだけの4つ（事件の一覧・事件の中身・答え合わせ待ちの予想・屋根裏の棚）。
 * 書き込み・答え合わせ・○×・状態を変える・直す・消す道具は置かない（tests/Feature/Ai/ReadOnlyToolsTest が確かめる）
 */
class SuiriServer extends Server
{
    /** AI が呼べる道具（読むだけ） */
    protected array $tools = [
        ListCases::class,
        GetCase::class,
        ListDuePredictions::class,
        SearchAttic::class,
    ];

    /** 読み物・頼み方の型は置かない */
    protected array $resources = [];

    /** 頼み方の型は置かない */
    protected array $prompts = [];

    /** 窓口を動かす前に、名前・版・説明（AI が最初に読む文）を config/ai_connect.php から入れる */
    protected function boot(): void
    {
        $this->name = config('ai_connect.server.name');
        $this->version = config('ai_connect.server.version');
        $this->instructions = config('ai_connect.instructions');
    }
}
