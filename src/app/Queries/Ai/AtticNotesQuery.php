<?php

namespace App\Queries\Ai;

use App\Models\AtticNote;
use App\Models\User;

/**
 * AI の道具 search_attic が返す、屋根裏の棚の知識を探す係（読むだけ）。
 * 本人の棚のうち、事件と結んでいない知識（手で足したもの）と、見える事件（仕事の場面でない）から来た知識。
 * 言葉が渡されたら「つまり？」（rule）か「たとえば？」（examples）に含むものに絞る。新しい順
 */
class AtticNotesQuery
{
    /**
     * 棚の知識を返す。$keyword が空なら絞らない
     *
     * @return array<int, array<string, mixed>>
     */
    public function for(User $user, ?string $keyword, int $limit): array
    {
        $like = '%'.addcslashes((string) $keyword, '%_\\').'%';

        return $user->atticNotes()
            ->where(fn ($q) => $q->whereNull('case_file_id')->orWhereHas('caseFile', fn ($c) => VisibleCases::scope($c)))
            ->when(filled($keyword), fn ($q) => $q->where(fn ($w) => $w->where('rule', 'like', $like)->orWhere('examples', 'like', $like)))
            ->with('caseFile:id,title')
            ->latest('id')->limit($limit)->get()
            ->map(fn (AtticNote $note) => [
                'id' => $note->id,
                'rule' => $note->rule,
                'examples' => $note->examples,
                'case' => $note->caseFile ? ['id' => $note->caseFile->id, 'title' => $note->caseFile->title] : null,
                'created_at' => $note->created_at?->toIso8601String(),
            ])->all();
    }
}
