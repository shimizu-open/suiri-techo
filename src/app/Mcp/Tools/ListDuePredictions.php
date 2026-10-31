<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\DescribedInConfig;
use App\Queries\Ai\DuePredictionsQuery;
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
 * AI の道具 list_due_predictions：確かめる日が来た（答え合わせ待ちの）予想を返す（読むだけ）。
 * 仕事の場面の事件と、相棒モードで見せ合う前の事件の予想は出ない
 */
#[Name('list_due_predictions')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class ListDuePredictions extends Tool
{
    use DescribedInConfig;

    /** 道具を呼ばれたら、答え合わせ待ちの予想を返す */
    public function handle(Request $request, DuePredictionsQuery $due): ResponseFactory
    {
        $data = $request->validate(['days_ahead' => ['nullable', 'integer', 'min:0', 'max:'.config('ai_connect.limits.due_days_max')]]);

        return Response::structured([
            'today' => today()->toDateString(),
            'predictions' => $due->for($request->user(), (int) ($data['days_ahead'] ?? 0)),
            'note' => config('ai_connect.messages.work_note'),
        ]);
    }

    /** AI に渡す、受け取る値の形を返す */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days_ahead' => $schema->integer()->min(0)->max(config('ai_connect.limits.due_days_max'))->default(0)
                ->description('何日先までに確かめる日が来るものを含めるか。0＝今日までに来たもの（初めの値）'),
        ];
    }
}
