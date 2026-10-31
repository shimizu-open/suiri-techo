<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Ai\RevokeAiAccess;
use App\Http\Controllers\Controller;
use App\Queries\Ai\ConnectedAppsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 設定の「つなぐのをやめる」（DELETE /settings/ai/{app}）。その AI の通行証をすべて取り消して、設定の画面へ戻す。
 * {app} は AI の登録の番号。その人がつないでいない AI（他人の AI・ない番号）なら 404
 */
class RevokeAiAppController extends Controller
{
    /** 取り消して、「◯◯ とつなぐのをやめたよ」と出して設定の画面へ戻す */
    public function __invoke(Request $request, string $app, ConnectedAppsQuery $apps, RevokeAiAccess $revoke): RedirectResponse
    {
        $connected = collect($apps->for($request->user()))->firstWhere('id', $app);
        abort_if($connected === null, 404);

        $revoke->handle($request->user(), $app);

        return redirect()->route('settings.show')->with('status', strtr(config('ai_connect.panel.stopped'), [':name' => $connected['name']]));
    }
}
