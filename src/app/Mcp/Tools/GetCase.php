<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\DescribedInConfig;
use App\Queries\Ai\CaseSheetQuery;
use App\Queries\Ai\VisibleCases;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * AI の道具 get_case：1つの事件の中身（なぞ・手がかり・見立て・予想と答え合わせ）を返す（読むだけ）。
 * 仕事の場面の事件・他人の事件・ない番号は、どれも同じ「見つかりません」の文にする（あるかどうかを漏らさない）
 */
#[Name('get_case')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class GetCase extends Tool
{
    use DescribedInConfig;

    /** 道具を呼ばれたら、事件の中身を返す。見えない事件なら決まった文の失敗を返す */
    public function handle(Request $request, VisibleCases $visible, CaseSheetQuery $sheet): Response|ResponseFactory
    {
        $data = $request->validate(['case_id' => ['required', 'integer', 'min:1']]);
        $case = $visible->find($request->user(), (int) $data['case_id']);

        // 本人の・仕事の場面でない事件が見つからなければ、理由を分けずに同じ文で知らせる
        if ($case === null) {
            return Response::error(config('ai_connect.messages.case_not_found'));
        }

        return Response::structured($sheet->for($case));
    }

    /** AI に渡す、受け取る値の形を返す */
    public function schema(JsonSchema $schema): array
    {
        return [
            'case_id' => $schema->integer()->min(1)->required()->description('事件の番号（list_cases の id）'),
        ];
    }
}
