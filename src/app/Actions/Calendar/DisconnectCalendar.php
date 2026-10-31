<?php

namespace App\Actions\Calendar;

use App\Contracts\CalendarApi;
use App\Models\User;
use App\Services\Google\GoogleTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * カレンダーとつなぐのをやめる用事（DELETE /calendar。退会のときにも使える）。
 * 1. 利用者が選んだら、専用カレンダー「推理手帳」を Google から消す（つながっているときだけ）
 * 2. Google 側で許可を取り消す（revoke）。失敗しても先へ進む
 * 3. タスクと予定のつながり（calendar_event_id）を外し、連携の行（暗号化したトークン）を消す
 * Google が止まっていても、手元の鍵は必ず消す
 */
class DisconnectCalendar
{
    /** Google カレンダーの窓口と、トークンの係を受け取る */
    public function __construct(private CalendarApi $api, private GoogleTokens $tokens) {}

    /** つなぐのをやめる。$deleteCalendar が true なら専用カレンダーも消す。消したら true、もともとつないでいなければ false */
    public function handle(User $user, bool $deleteCalendar = false): bool
    {
        $connection = $user->calendarConnection;

        // もともとつないでいなければ、何もしない
        if ($connection === null) {
            return false;
        }

        // 専用カレンダーも消すと選ばれ、まだつながっているなら、Google から消す
        if ($deleteCalendar && $connection->isActive() && filled($connection->app_calendar_id)) {
            // 専用カレンダーを消してみる。失敗しても（Google の不調・許可切れ）つなぐのをやめるのは続ける
            try {
                $this->api->deleteCalendar($connection, $connection->app_calendar_id);
            } catch (Throwable $e) {
                Log::info('専用カレンダーを消せなかった', ['connection_id' => $connection->id, 'error' => class_basename($e)]);
            }
        }

        $this->tokens->revoke($connection);

        DB::transaction(function () use ($user, $connection) {
            $user->tasks()->whereNotNull('calendar_event_id')->update(['calendar_event_id' => null, 'calendar_synced_at' => null]);
            $connection->delete();
        });
        $user->unsetRelation('calendarConnection');

        return true;
    }
}
