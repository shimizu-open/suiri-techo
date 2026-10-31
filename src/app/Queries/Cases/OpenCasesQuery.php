<?php

namespace App\Queries\Cases;

use App\Enums\CaseStatus;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 今日の画面に出す「捜査中の事件」（最近さわった順に数件）を組み立てる係。
 * <x-today.open-cases :cases="…"> にそのまま渡せる（数は config('suiri.today.open_cases')）
 */
class OpenCasesQuery
{
    /** 捜査中の事件を、手がかり・見立て・答え合わせ待ちの数つきで返す */
    public function get(User $user): Collection
    {
        return CaseListQuery::withCounts($user->caseFiles())
            ->where('status', CaseStatus::Open)
            ->latest('updated_at')->latest('id')
            ->limit(config('suiri.today.open_cases'))
            ->get();
    }
}
