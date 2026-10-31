<?php

namespace App\Http\Controllers\Calendar;

use App\Actions\Calendar\ConnectCalendar;
use App\Http\Controllers\Controller;
use App\Services\Google\Exceptions\CalendarConnectRefused;
use App\Services\Google\GoogleTokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Google カレンダーとつなぐ受付（ログインとは別に、あとから許可をもらう「段階的な許可」）。
 * 1. show：つなぐ前の説明（送る項目・受け取る項目・Google は米国の会社・いつでもやめられる）
 * 2. redirect：同意したら、カレンダーのスコープとオフラインの許可を足して Google の画面へ送る
 * 3. callback：Google から戻ったら、ConnectCalendar が確かめて保存し、設定の画面へ戻す
 */
class ConnectCalendarController extends Controller
{
    /** つなぐ前の説明の画面を返す（GET /calendar/connect）。Google の鍵が未設定なら「準備中」と出し、進むボタンは出さない */
    public function show(Request $request): View
    {
        return view('calendar.connect', [
            'ready' => GoogleTokens::configured(),
            'connection' => $request->user()->calendarConnection,
        ]);
    }

    /**
     * Google の画面へ送り出す（POST /calendar/connect）。with() は前の値を上書きするので1回にまとめる。
     * access_type=offline と prompt=consent で refresh token をもらい、include_granted_scopes でログインの許可も含める
     */
    public function redirect(Request $request): RedirectResponse|SymfonyRedirect
    {
        // Google の鍵がまだ設定されていなければ、設定の画面へ戻して「準備中」と知らせる
        if (! GoogleTokens::configured()) {
            return redirect()->route('settings.show')->with('error', 'Google カレンダーとの連携は準備中です。');
        }

        return Socialite::driver('google')
            ->redirectUrl(route('calendar.callback'))
            ->scopes(config('suiri.calendar.scopes'))
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
                'login_hint' => $request->user()->email,
            ])
            ->enablePKCE()
            ->redirect();
    }

    /** Google から戻ってくる先（GET /calendar/callback）。確かめて保存し、設定の画面へ戻して結果を知らせる */
    public function callback(Request $request, ConnectCalendar $connect): RedirectResponse
    {
        // Google の鍵がまだ設定されていなければ、設定の画面へ戻して「準備中」と知らせる
        if (! GoogleTokens::configured()) {
            return redirect()->route('settings.show')->with('error', 'Google カレンダーとの連携は準備中です。');
        }

        // Google から許可を受け取る（送り出しと同じ戻り先と PKCE で）。やめた・時間切れなどで失敗したら、設定の画面で知らせる
        try {
            $google = Socialite::driver('google')->redirectUrl(route('calendar.callback'))->enablePKCE()->user();
        } catch (Throwable $e) {
            Log::info('カレンダーとつなぐのを中断', ['user_id' => $request->user()->id, 'error' => class_basename($e)]);

            return redirect()->route('settings.show')->with('error', 'Google カレンダーとつなげませんでした。もう一度お試しください。');
        }

        // 確かめて保存する。別のアカウント・許可が足りない・鍵がないなどで断られたら、その理由を出す
        try {
            $connect->handle($request->user(), $google);
        } catch (CalendarConnectRefused $e) {
            return redirect()->route('settings.show')->with('error', $e->getMessage());
        }

        return redirect()->route('settings.show')
            ->with('status', 'Google カレンダーとつなぎました。専用のカレンダー「'.config('suiri.calendar.name').'」に、期日のあるタスクを書き込みます。');
    }
}
