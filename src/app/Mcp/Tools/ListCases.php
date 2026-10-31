<?php

namespace App\Mcp\Tools;

use App\Enums\CaseStatus;
use App\Mcp\Concerns\DescribedInConfig;
use App\Queries\Ai\CaseListQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * AI の道具 list_cases：本人の事件の一覧を返す（読むだけ。仕事の場面の事件は出ない）。
 * 受付と同じく薄くする：形を確かめる → CaseListQuery で組み立てる → 形にして返す
 */
#[Name('list_cases')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class ListCases extends Tool
{
    use DescribedInConfig;

    /** 道具を呼ばれたら、一覧を返す */
    public function handle(Request $request, CaseListQuery $cases): ResponseFactory
    {
        $limits = config('ai_connect.limits');
        $data = $request->validate([
            'status' => ['nullable', Rule::in([...CaseStatus::values(), 'all'])],
            'keyword' => ['nullable', 'string', 'max:'.$limits['keyword']],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$limits['cases_max']],
        ]);
        $list = $cases->for($request->user(), $data['status'] ?? CaseStatus::Open->value, $data['keyword'] ?? null, (int) ($data['limit'] ?? $limits['cases_default']));

        return Response::structured(['cases' => $list, 'note' => config('ai_connect.messages.work_note')]);
    }

    /** AI に渡す、受け取る値の形を返す */
    public function schema(JsonSchema $schema): array
    {
        $limits = config('ai_connect.limits');

        return [
            'status' => $schema->string()->enum([...CaseStatus::values(), 'all'])->default(CaseStatus::Open->value)
                ->description('open＝捜査中（初めの値）・solved＝解決・cold＝迷宮入り・all＝すべて'),
            'keyword' => $schema->string()->description('題名（なぞ）に含む言葉。'.$limits['keyword'].'字まで。なくてもよい'),
            'limit' => $schema->integer()->min(1)->max($limits['cases_max'])->default($limits['cases_default'])->description('返す件数'),
        ];
    }
}
