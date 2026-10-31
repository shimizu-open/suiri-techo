<?php

namespace App\Actions\Ai;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

/**
 * 設定の「つなぐのをやめる」の用事。その人がその AI に出した通行証・更新の札・引換券をすべて取り消す。
 * Passport は窓口を呼ばれるたびに取り消したかを見るので、すぐに効く（次の呼び出しから 401）。もう一度つなぐときは同意の画面からやり直す。
 * 他人の分には触れない（すべて user_id で絞る）。取り消した通行証の行は、毎日の片づけ（passport:purge）が消す
 */
class RevokeAiAccess
{
    /** 取り消して、取り消した通行証の数を返す（0 なら、その人はその AI とつないでいなかった）。途中で失敗したら何も変えない */
    public function handle(User $user, string $appId): int
    {
        return DB::transaction(function () use ($user, $appId) {
            $tokenIds = Passport::token()->newQuery()->where('user_id', $user->id)->where('client_id', $appId)->pluck('id');
            Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokenIds)->update(['revoked' => true]);
            Passport::authCode()->newQuery()->where('user_id', $user->id)->where('client_id', $appId)->update(['revoked' => true]);

            return Passport::token()->newQuery()->whereKey($tokenIds)->where('revoked', false)->update(['revoked' => true]);
        });
    }
}
