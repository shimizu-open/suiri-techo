<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\DescribedInConfig;
use App\Queries\Ai\AtticNotesQuery;
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
 * AI の道具 search_attic：屋根裏の棚（つまり？・たとえば？）を探して返す（読むだけ）。
 * 仕事の場面の事件から来た知識は出ない。事件と結んでいない（手で足した）知識は出る
 */
#[Name('search_attic')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class SearchAttic extends Tool
{
    use DescribedInConfig;

    /** 道具を呼ばれたら、棚の知識を返す */
    public function handle(Request $request, AtticNotesQuery $attic): ResponseFactory
    {
        $limits = config('ai_connect.limits');
        $data = $request->validate([
            'keyword' => ['nullable', 'string', 'max:'.$limits['keyword']],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$limits['attic_max']],
        ]);

        return Response::structured([
            'notes' => $attic->for($request->user(), $data['keyword'] ?? null, (int) ($data['limit'] ?? $limits['attic_default'])),
        ]);
    }

    /** AI に渡す、受け取る値の形を返す */
    public function schema(JsonSchema $schema): array
    {
        $limits = config('ai_connect.limits');

        return [
            'keyword' => $schema->string()->description('「つまり？」か「たとえば？」に含む言葉。'.$limits['keyword'].'字まで。なくてもよい'),
            'limit' => $schema->integer()->min(1)->max($limits['attic_max'])->default($limits['attic_default'])->description('返す件数'),
        ];
    }
}
