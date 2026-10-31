<?php

namespace App\Queries\Ai;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;

/**
 * その人が「つないでいる AI」（ChatGPT・Claude など）の一覧を組み立てる係（設定の部品と、データの書き出しで使う）。
 * つないでいる＝取り消していない通行証があり、その通行証か更新の札がまだ期限の中。AI（登録の番号）ごとに1行にまとめる。
 * 1行の中身：登録の番号・AI が名乗った名前・戻る先の住所・最後につながった日時（いちばん新しい通行証を出した日時）。通行証の番号は出さない
 */
class ConnectedAppsQuery
{
    /**
     * つないでいる AI の一覧を、最後につながった日時の新しい順に返す
     *
     * @return array<int, array{id: string, name: string, host: string, last_at: Carbon}>
     */
    public function for(User $user): array
    {
        $now = now();
        $tokens = Passport::token()->newQuery()
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->where(fn ($q) => $q->where('expires_at', '>', $now)
                ->orWhereHas('refreshToken', fn ($r) => $r->where('revoked', false)->where('expires_at', '>', $now)))
            ->get(['id', 'client_id', 'created_at']);
        $apps = Passport::client()->newQuery()->whereIn('id', $tokens->pluck('client_id')->unique())->get(['id', 'name', 'redirect_uris'])->keyBy('id');

        return $tokens->groupBy('client_id')
            ->map(fn ($group, $appId) => [
                'id' => (string) $appId,
                'name' => $apps[$appId]->name ?? '',
                'host' => (string) parse_url($apps[$appId]->redirect_uris[0] ?? '', PHP_URL_HOST),
                'last_at' => $group->max(fn (Token $token) => $token->created_at),
            ])
            ->sortByDesc('last_at')->values()->all();
    }
}
