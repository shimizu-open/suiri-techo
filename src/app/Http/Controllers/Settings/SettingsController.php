<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateSettings;
use App\Enums\Scene;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Queries\Ai\ConnectedAppsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 設定の画面（GET/PATCH /settings）。表示名・相棒の名前・ふだんの場面・記録のきっかけを直す。
 * 下の方のカレンダーの枠・ChatGPT と Claude とつなぐ枠・データのダウンロードと退会の枠は、それぞれの担当の部品を置く
 */
class SettingsController extends Controller
{
    /** 設定の画面を返す（つないでいる AI の一覧は ConnectedAppsQuery が組み立てる。本人の分だけ） */
    public function show(Request $request, ConnectedAppsQuery $connectedApps): View
    {
        $user = $request->user()->load('calendarConnection');

        return view('settings.show', [
            'user' => $user,
            'scenes' => Scene::options(),
            'cueExamples' => config('suiri.cue_examples'),
            'aiApps' => $connectedApps->for($user),
        ]);
    }

    /** 設定を保存して、設定の画面へ戻す（形の確かめは UpdateSettingsRequest、保存は UpdateSettings） */
    public function update(UpdateSettingsRequest $request, UpdateSettings $updateSettings): RedirectResponse
    {
        $updateSettings->handle($request->user(), $request->validated());

        return redirect()->route('settings.show')->with('status', config('suiri.settings.saved'));
    }
}
