<?php

namespace App\Actions\Calendar;

use App\Contracts\CalendarApi;
use App\Enums\CalendarStatus;
use App\Jobs\PushAllTasksToCalendar;
use App\Models\CalendarConnection;
use App\Models\User;
use App\Services\Google\Exceptions\CalendarConnectRefused;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Two\User as GoogleUser;
use Throwable;

/**
 * Google から戻ってきた許可で、カレンダーとつなぐ用事（Calendar\ConnectCalendarController の戻りが呼ぶ）。
 * 1. 確かめる：①ログイン中と同じ Google アカウント（sub）②求めたスコープがすべて許可された ③refresh token がある
 * 2. 専用カレンダー「推理手帳」を用意する（前に作ったものが残っていれば使い、なければ作る）
 * 3. トークンを暗号化して保存し（calendar_connections）、タスクをまとめて書き出す仕事を出す
 *    （PushAllTasksToCalendar。連携が切れている間に消したタスクの予定を、専用カレンダーから消すのもこの仕事）
 * どこかでだめなら何も保存せず、CalendarConnectRefused（利用者に向けた文）を投げる
 */
class ConnectCalendar
{
    /** Google カレンダーの窓口を受け取る */
    public function __construct(private CalendarApi $api) {}

    /** つないで、保存した連携を返す */
    public function handle(User $user, GoogleUser $google): CalendarConnection
    {
        $this->check($user, $google);

        $connection = $user->calendarConnection ?? new CalendarConnection;
        $connection->fill([
            'access_token' => $google->token,
            'refresh_token' => $google->refreshToken,
            'expires_at' => now()->addSeconds((int) ($google->expiresIn ?: 0)),
            'scopes' => array_values($google->approvedScopes),
            'status' => CalendarStatus::Active,
        ]);

        $calendarId = $this->prepareCalendar($connection);
        $isNewCalendar = $calendarId !== $connection->app_calendar_id;

        DB::transaction(function () use ($user, $connection, $calendarId, $isNewCalendar) {
            // 新しい専用カレンダーにしたなら、前のカレンダーの予定とのつながりを外し、差分の印も捨てる（全部書き直す）
            if ($isNewCalendar) {
                $user->tasks()->whereNotNull('calendar_event_id')->update(['calendar_event_id' => null, 'calendar_synced_at' => null]);
                $connection->sync_token = null;
            }
            $connection->app_calendar_id = $calendarId;
            $connection->user_id = $user->id;
            $connection->save();
        });

        PushAllTasksToCalendar::dispatch($user->id);

        return $connection;
    }

    /** 戻ってきた許可を確かめる。足りなければ CalendarConnectRefused */
    private function check(User $user, GoogleUser $google): void
    {
        // ログイン中とは別の Google アカウントで許可されたなら、つながない（ほかの人のカレンダーに書かないため）
        if ((string) $google->getId() !== (string) $user->google_id) {
            throw new CalendarConnectRefused('ログイン中と同じ Google アカウントを選んで、もう一度つないでください。');
        }

        $missing = array_diff(config('suiri.calendar.scopes'), (array) $google->approvedScopes);

        // 求めた許可の一部が外されていたら、つながない（Google の画面で、すべての項目にチェックが要る）
        if ($missing !== []) {
            throw new CalendarConnectRefused('カレンダーの許可が足りませんでした。Google の画面で、すべての項目にチェックを入れて、もう一度つないでください。');
        }

        // あとで鍵を取り直すための refresh token がなければ、つながない（すぐに使えなくなるため）
        if (blank($google->refreshToken)) {
            throw new CalendarConnectRefused('Google から必要な鍵を受け取れませんでした。もう一度つないでください。');
        }
    }

    /** 専用カレンダーの ID を返す。前に作ったものが Google に残っていればそれを使い、なければ新しく作る */
    private function prepareCalendar(CalendarConnection $connection): string
    {
        // 前の専用カレンダーが残っているか確かめ、なければ作る。Google が止まっていたら、つながずに知らせる
        try {
            // 前につないだときの専用カレンダーがまだあれば、それを使う（予定が二重にならない）
            if (filled($connection->app_calendar_id) && $this->api->hasCalendar($connection, $connection->app_calendar_id)) {
                return $connection->app_calendar_id;
            }

            return $this->api->insertCalendar($connection, config('suiri.calendar.name'), config('suiri.calendar.timezone'));
        } catch (Throwable) {
            throw new CalendarConnectRefused('Google カレンダーに専用のカレンダーを用意できませんでした。時間をおいて、もう一度つないでください。');
        }
    }
}
