<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * どの画面にも付ける「守りの札」（セキュリティのヘッダ）。
 * 別のサイトに画面を埋め込まれたり、ファイルの種類を取り違えられたりしたときの被害を小さくする。
 * ログイン中の人への応答は、ブラウザにも CloudFront にも取り置かせない（Cache-Control: private, no-store）
 */
class SecurityHeaders
{
    /**
     * 先に画面を作らせ、できた応答に守りの札を足して返す。
     * すべての画面に付く（bootstrap/app.php で append している）
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // ファイルの種類をブラウザに推測させない
            'X-Content-Type-Options' => 'nosniff',
            // ほかのサイトの枠（iframe）の中に、この画面を表示させない（クリックをだます攻撃の対策）
            'X-Frame-Options' => 'SAMEORIGIN',
            // ほかのサイトへ移るとき、URL の詳しい部分を渡さない
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // カメラ・マイク・位置情報などは使わない
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ];

        // 「次からも必ず https で来て」と伝える。https で届いたときと本番のとき（本番は CloudFront の https からしか入れないが、
        // CloudFront からサーバーまでは http なので、アプリからは https に見えない）。ローカルの http では付けない
        if ($request->isSecure() || app()->isProduction()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains'; // 1年間覚えさせる
        }

        // 決めた札を1つずつ取り出して、応答に付ける
        foreach ($headers as $name => $value) {
            // まだ付いていない札だけ付ける。画面ごとに先に付けた値は上書きしない
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // ログイン中の人への応答なら、自分の記録が端末や CloudFront に残らないよう、取り置きを禁じる
        if ($request->hasSession() && $request->user() !== null) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
