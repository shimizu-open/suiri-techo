<?php

namespace App\Queries\Ai;

use App\Enums\Scene;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AI の窓口（/mcp）から見える事件を決める係。決まりはここ1か所：
 * 本人の事件だけ（user_id で絞る）・仕事の場面（scene = work）の事件は見せない（施術の場面には体のことが混ざりうるため）。
 * 予想・屋根裏の棚は「事件が見えるか」で絞る（各 Query がここの scope を使う）
 */
class VisibleCases
{
    /** 窓口から見える、その人の事件の問い合わせを返す */
    public function of(User $user): HasMany
    {
        return self::scope($user->caseFiles());
    }

    /** 番号で1件を探す。仕事の場面・他人の事件・ない番号は、どれも null にする（あるかどうかを AI に漏らさない） */
    public function find(User $user, int $id): ?CaseFile
    {
        return $this->of($user)->whereKey($id)->first();
    }

    /** 事件の問い合わせに「仕事の場面を除く」を足して返す（whereHas の中でも使う） */
    public static function scope(Builder|HasMany $query): Builder|HasMany
    {
        return $query->where('scene', '!=', Scene::Work->value);
    }

    /** 事件の問い合わせに「相棒モードで見せ合う前の事件を除く」を足して返す（見立てと予想を隠すため） */
    public static function showingHypotheses(Builder|HasMany $query): Builder|HasMany
    {
        return $query->where(fn (Builder $q) => $q->where('partner_mode', false)->orWhereNotNull('revealed_at'));
    }
}
