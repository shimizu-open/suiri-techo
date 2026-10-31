<?php

namespace App\Providers;

use App\Support\Ai\ConsentView;
use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;

/**
 * 「ChatGPT・Claude とつなぐ」（読むだけの窓口 /mcp と、通行証を出す OAuth）の準備をする係。
 * - Passport の住所は Passport に作らせず、routes/ai.php と routes/web/ai.php で同じ名前のまま自分で置く
 *   （同意の画面に推理手帳の「ログイン・同意済み」の確かめを付けるため。デバイスの許可や JSON の API は置かない）
 * - 同意の画面は自前（views/oauth/authorize）。laravel/mcp の見本は外部の書体と回る動きを使うので使わない
 * - 通行証の期限は1時間、更新の札は30日（Passport の既定の1年にしない）。範囲は mcp:use の1つだけ
 * - 回数制限：窓口（人ごと）・OAuth の案内と通行証（IP ごと）・AI の自己登録（IP ごと・1時間）
 */
class AiConnectServiceProvider extends ServiceProvider
{
    /** Passport が自分で住所を作らないようにし、使わない許可（デバイス）を切る。Passport の起動より前に決める */
    public function register(): void
    {
        Passport::ignoreRoutes();
        Passport::$deviceCodeGrantEnabled = false;
    }

    /** 同意の画面・通行証の期限・既定の範囲・回数制限を決める */
    public function boot(): void
    {
        Passport::authorizationView(fn (array $parameters) => view('oauth.authorize', ConsentView::from($parameters)));
        Passport::tokensExpireIn(CarbonInterval::minutes(config('ai_connect.tokens.access_minutes')));
        Passport::refreshTokensExpireIn(CarbonInterval::days(config('ai_connect.tokens.refresh_days')));
        // AI が範囲を指定しなくても、窓口を読む範囲（mcp:use）の通行証にする
        Passport::defaultScopes([Registrar::OAUTH_SCOPE]);

        // AI の会社のサーバーの IP は、その会社の利用者みんなで同じなので、IP ごとの数はきつくしすぎない
        RateLimiter::for('mcp', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?? $r->ip()));  // 窓口の道具を呼ぶ
        RateLimiter::for('oauth', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));                    // 案内・通行証の受け渡し
        RateLimiter::for('oauth-register', fn (Request $r) => Limit::perHour(20)->by($r->ip()));             // AI の自己登録
    }
}
