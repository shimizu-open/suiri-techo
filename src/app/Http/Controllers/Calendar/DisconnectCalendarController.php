<?php

namespace App\Http\Controllers\Calendar;

use App\Actions\Calendar\DisconnectCalendar;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google カレンダーとつなぐのをやめる受付（DELETE /calendar）。ログイン中の本人の連携だけを扱う（住所に他人の id を受け取らない）。
 * 「専用カレンダーも消す」にチェックがあれば、Google の専用カレンダーも消す
 */
class DisconnectCalendarController extends Controller
{
    /** つなぐのをやめて、設定の画面へ戻す */
    public function __invoke(Request $request, DisconnectCalendar $disconnect): RedirectResponse
    {
        $disconnect->handle($request->user(), $request->boolean('delete_calendar'));

        return redirect()->route('settings.show')->with('status', 'Google カレンダーとつなぐのをやめました。');
    }
}
