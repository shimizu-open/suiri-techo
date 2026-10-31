<?php

namespace App\Queries\Today;

use App\Models\Prediction;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 今日の画面の「答え合わせ待ち」を集める係。
 * その人の予想のうち、まだ結果がなく（result が null）、取り下げてもおらず、確かめる日が今日までに来ているもの。
 * 確かめる日の古い順（過ぎているものが上）、同じ日なら時刻の早い順。事件の題名と場面も一緒に読む（行に出すため）
 */
class DuePredictionsQuery
{
    /**
     * 答え合わせ待ちの予想を返す（$today は 'Y-m-d'）
     *
     * @return Collection<int, Prediction>
     */
    public function for(User $user, string $today): Collection
    {
        return $user->predictions()
            ->whereNull('result')
            ->whereNull('withdrawn_at')
            ->where('check_on', '<=', $today)
            ->with('caseFile:id,title,scene')
            ->orderBy('check_on')->orderBy('check_at')->orderBy('id')
            ->get();
    }
}
