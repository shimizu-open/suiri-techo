<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AI の窓口（/mcp）に、よその画面（ブラウザ）から呼び出されたら断る係（routes/ai.php で付ける）。
 * MCP の決まりで、窓口は Origin（どの画面から来たか）を確かめることになっている（DNS の書き換えを使った攻撃の対策）。
 * AI の会社のサーバーからの呼び出しには Origin が付かないので通る。付いていれば、推理手帳自身か、
 * 使う AI のサービスの住所（config/mcp.php の redirect_domains：chatgpt.com・claude.ai・claude.com）だけ通す
 */
class RejectForeignOrigin
{
    /** Origin が付いていて、認めた住所でなければ 403 を返す。そうでなければそのまま窓口へ進める */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        // Origin が付いていて、推理手帳自身でも、使う AI のサービスでもなければ断る
        if ($origin !== null && ! $this->allowed($origin)) {
            return response()->json(['error' => 'forbidden_origin'], 403);
        }

        return $next($request);
    }

    /** その Origin（https://chatgpt.com など）を認めるかを返す。末尾の / は付けずに比べる */
    private function allowed(string $origin): bool
    {
        $allowed = [rtrim(url('/'), '/'), ...config('mcp.redirect_domains', [])];

        return in_array(Str::of($origin)->rtrim('/')->toString(), array_map(fn (string $url) => rtrim($url, '/'), $allowed), true);
    }
}
