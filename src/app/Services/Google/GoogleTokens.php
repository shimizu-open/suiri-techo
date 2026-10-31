<?php

namespace App\Services\Google;

use App\Enums\CalendarStatus;
use App\Models\CalendarConnection;
use App\Services\Google\Exceptions\GoogleApiFailed;
use App\Services\Google\Exceptions\GoogleAuthBroken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google の鍵（トークン）の係。
 * - accessToken()：期限が近ければ refresh token で取り直してから、使える access token を返す
 * - expire()：Google に 401 と言われたとき、次の問い合わせで取り直すよう期限切れにする
 * - revoke()：つなぐのをやめる・退会のときに、Google 側で許可を取り消す
 * トークンそのものはログに書かない
 */
class GoogleTokens
{
    /** Google の鍵（クライアント ID とシークレット）が両方設定されているかを返す。未設定なら「準備中」と出す */
    public static function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /** 使える access token を返す。期限の少し前（config の refresh_before_seconds）を過ぎていたら、先に取り直す */
    public function accessToken(CalendarConnection $connection): string
    {
        $soon = now()->addSeconds(config('suiri.calendar.refresh_before_seconds'));

        // 期限が分からないか、もうすぐ切れるなら、refresh token で取り直す
        if ($connection->expires_at === null || $connection->expires_at->lte($soon)) {
            $this->refresh($connection);
        }

        return (string) $connection->access_token;
    }

    /**
     * refresh token で access token を取り直して保存する（POST https://oauth2.googleapis.com/token）。
     * invalid_grant（許可が取り消された・切れた）なら連携を broken にして GoogleAuthBroken、そのほかの失敗は GoogleApiFailed
     */
    public function refresh(CalendarConnection $connection): void
    {
        // Google に取り直しを頼む。つながらなければ、あとで試し直せるよう GoogleApiFailed にする
        try {
            $response = Http::asForm()->timeout(config('suiri.calendar.timeout_seconds'))->post(config('suiri.calendar.urls.token'), [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ]);
        } catch (ConnectionException) {
            throw new GoogleApiFailed('token', 0);
        }

        // 許可が取り消された・切れたなら、連携を「切れた」にして同期を止める（画面で「つなぎ直してください」と出る）
        if ($response->json('error') === 'invalid_grant') {
            $this->markBroken($connection);
            throw new GoogleAuthBroken;
        }

        // そのほかの失敗（Google の不調など）は、連携はそのままにして、あとで試し直す
        if ($response->failed() || blank($response->json('access_token'))) {
            throw new GoogleApiFailed('token', $response->status());
        }

        $connection->forceFill([
            'access_token' => $response->json('access_token'),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600)),
        ]);

        // Google が新しい refresh token もくれたときだけ、入れ替える（ふつうは返ってこない）
        if (filled($response->json('refresh_token'))) {
            $connection->refresh_token = $response->json('refresh_token');
        }

        $this->saveIfStored($connection);
    }

    /** access token を期限切れにする（Google に 401 と言われたとき。次の問い合わせで取り直す） */
    public function expire(CalendarConnection $connection): void
    {
        $connection->expires_at = now()->subSecond();
        $this->saveIfStored($connection);
    }

    /**
     * Google 側で許可を取り消す（POST https://oauth2.googleapis.com/revoke）。うまくいったら true。
     * 失敗しても例外にしない（つなぐのをやめる・退会は、手元の鍵を消すところまで必ず進める）
     */
    public function revoke(CalendarConnection $connection): bool
    {
        // refresh token を取り消す（対応する access token も一緒に無効になる）。つながらないなどの失敗は false にする
        try {
            return Http::asForm()->timeout(config('suiri.calendar.timeout_seconds'))
                ->post(config('suiri.calendar.urls.revoke'), ['token' => $connection->refresh_token])
                ->successful();
        } catch (Throwable $e) {
            Log::info('Google の許可の取り消しに失敗', ['connection_id' => $connection->id, 'error' => class_basename($e)]);

            return false;
        }
    }

    /** 連携を「切れた」にして保存する。記録には連携の id だけを書く */
    private function markBroken(CalendarConnection $connection): void
    {
        $connection->status = CalendarStatus::Broken;
        $this->saveIfStored($connection);
        Log::info('Google カレンダーの許可が切れた', ['connection_id' => $connection->id]);
    }

    /** 表にすでにある連携だけ保存する（つなぐ途中のまだ保存していない連携は、つなぐ用事が保存する） */
    private function saveIfStored(CalendarConnection $connection): void
    {
        // まだ表にない（つなぐ途中の）連携なら、ここでは保存しない
        if ($connection->exists) {
            $connection->save();
        }
    }
}
