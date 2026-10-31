<?php

use App\Http\Middleware\EnsureAgreed;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Landing;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php', // 中身は routes/web/{機能}.php。web.php はそれを読み込むだけ
        commands: __DIR__.'/../routes/console.php',
        health: '/up', // 死活確認の住所（アプリが動いているか）
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'agreed' => EnsureAgreed::class, // ルートで middleware('agreed') と書くと、同意前の人を /agree へ送る
        ]);
        // どの画面にも守りの札（セキュリティのヘッダ）を付ける。ログイン中の応答は取り置かせない
        $middleware->append(SecurityHeaders::class);
        // ログインが必要な画面に、ログインしていない人が来たときの行き先（トップ）。
        // AI（ChatGPT・Claude）の同意の画面から来た人だけは、トップのボタンを1回減らすため Google のログインへ直接送る（ログイン後にここへ戻る）
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('oauth/authorize') ? route('auth.google') : route('home'));
        // ログイン前だけの画面（Google へ送り出すなど）に、ログイン済みの人が来たときの行き先（最初の画面）
        $middleware->redirectUsersTo(fn () => Landing::url());
        // 本番では CloudFront の後ろで動く。CloudFront が付ける X-Forwarded-For から本当のアクセス元を取る（回数制限に使う）。
        // EC2 には CloudFront からしか届かない前提（セキュリティグループで絞る）なので、送り元は限定しない
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen( // fetch など JSON を求める要求と、AI の窓口（/mcp）・通行証の住所のエラーは JSON で返す
            fn (Request $request) => $request->is('api/*', 'mcp', 'oauth/token', 'oauth/register') || $request->expectsJson(),
        );
    })->create();
