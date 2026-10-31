<?php

use App\Http\Middleware\RejectForeignOrigin;
use App\Mcp\Servers\SuiriServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
| ChatGPT・Claude がつなぐ窓口の住所（機能：AI とつなぐ）。laravel/mcp がミドルウェアのグループなしで読み込む
| （セッションも CSRF もない＝AI の会社のサーバーからの POST が通る）。代わりに窓口は通行証（auth:api）が必須。
| 同意の画面（/oauth/authorize）はログインの Cookie が要るので routes/web/ai.php に置く。
|
| ① GET /.well-known/oauth-protected-resource/mcp・/.well-known/oauth-authorization-server … 通行証の出し方の案内
| ② POST /oauth/register … AI の自己登録（戻り先は config/mcp.php の chatgpt.com・claude.ai・claude.com だけ）
| ③ POST /oauth/token … 引換券（＋PKCE の合言葉）や更新の札を、通行証に引き換える
| ④ POST /mcp … 窓口そのもの（読むだけの道具4つ。GET・DELETE は 405）
*/

// ①〜③：IP ごとの回数制限（AI の会社のサーバーの IP は利用者みんなで同じなので、きつくしすぎない）
Route::middleware('throttle:oauth')->group(function () {
    // 案内の住所は laravel/mcp に作らせる。登録の住所は、回数制限を足すため下で自分で置く
    Mcp::oauthRoutes(registration: false);
    Route::post('/oauth/register', OAuthRegisterController::class)->middleware('throttle:oauth-register')->name('mcp.oauth.register');
    // Passport の住所は AiConnectServiceProvider で止めているので、同じ名前のまま置く（案内の中身が route('passport.token') で作られる）
    Route::post('/oauth/token', [AccessTokenController::class, 'issueToken'])->name('passport.token');
});

// ④：よその画面からの呼び出しを断る → 通行証を確かめる → 窓口を読む範囲（mcp:use）かを確かめる → 人ごとの回数制限
Mcp::web('/mcp', SuiriServer::class)->middleware([
    RejectForeignOrigin::class,
    'auth:api',
    CheckToken::using(Registrar::OAUTH_SCOPE),
    'throttle:mcp',
]);
