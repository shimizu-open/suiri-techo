<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 利用規約・プライバシーポリシー・年齢の確認に同意した人だけを通す（ルートでは middleware('agreed')）。
 * まだの人は同意の画面（/agree）へ送る。ログインが要る画面には auth と一緒に付ける
 */
class EnsureAgreed
{
    /** 同意済みならそのまま進め、まだなら同意の画面へ送る */
    public function handle(Request $request, Closure $next): Response
    {
        // ログイン中で、まだ同意していなければ、同意の画面へ送る（同意したあとは元の画面へ戻れるよう覚えておく）
        if ($request->user() !== null && ! $request->user()->hasAgreed()) {
            return redirect()->guest(route('agree.show'));
        }

        return $next($request);
    }
}
