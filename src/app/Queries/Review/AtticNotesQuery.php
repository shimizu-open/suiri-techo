<?php

namespace App\Queries\Review;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 屋根裏の棚（R-5）の画面に出すものを組み立てる：その人の知識の一覧（新しい順・生まれた事件と、使った予想の数つき）と、
 * 「どの事件から？」の選択肢
 */
class AtticNotesQuery
{
    /** 知識の一覧を返す。1ページの件数は config('suiri.review.attic_per_page') */
    public function notes(User $user): LengthAwarePaginator
    {
        return $user->atticNotes()
            ->with('caseFile:id,user_id,title')
            ->withCount('predictions')
            ->latest('id')
            ->paginate(config('suiri.review.attic_per_page'));
    }

    /** 「どの事件から？」の選択肢を「事件の id => なぞ」の配列で返す（その人の事件の新しい順） */
    public function caseOptions(User $user): array
    {
        return $user->caseFiles()
            ->latest('id')
            ->limit(config('suiri.review.case_options'))
            ->pluck('title', 'id')
            ->all();
    }
}
