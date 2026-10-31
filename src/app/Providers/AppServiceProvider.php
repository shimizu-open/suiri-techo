<?php

namespace App\Providers;

use App\Contracts\CalendarAgenda;
use App\Models\CaseFile;
use App\Services\Google\NullCalendarAgenda;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * アプリ全体の準備をする係。名前つきの回数制限、ルートの {case} と事件（CaseFile）の結びつけ、本番の https をここで決める
 */
class AppServiceProvider extends ServiceProvider
{
    /** 係（サービス）をアプリに登録する。今日の予定の窓口は、カレンダーの担当が本物に差し替えるまで「いつも空」の実装を使う */
    public function register(): void
    {
        $this->app->bind(CalendarAgenda::class, NullCalendarAgenda::class);
    }

    /** アプリが起動したあとの設定をする：回数制限・{case} の結びつけ・本番での https の強制 */
    public function boot(): void
    {
        // 回数制限（連打・総当たり・自動化の対策）。種類ごとに別々に数える。ルートでは throttle:auth のように名前で使う
        $by = fn (Request $request) => $request->user()?->id ?? $request->ip(); // ログイン中は人ごと、ログイン前は IP ごとに数える
        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));       // Google へのログイン・戻り
        RateLimiter::for('writes', fn (Request $r) => Limit::perMinute(60)->by($by($r)));      // 記録を書く（作る・直す・消す）
        RateLimiter::for('account', fn (Request $r) => Limit::perMinute(5)->by($by($r)));      // データのダウンロード・退会
        RateLimiter::for('calendar', fn (Request $r) => Limit::perMinute(10)->by($by($r)));    // カレンダーとつなぐ・やめる

        // 住所の {case} は「事件」（CaseFile）。case は PHP の予約語なのでモデルの名前と住所の名前がちがう
        Route::model('case', CaseFile::class);

        // CloudFront の後ろ（EC2 には http で届く）でも、本番では https の URL を作る。
        // これがないと、フォームの送信先や Google の戻り先が http になってしまう
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
