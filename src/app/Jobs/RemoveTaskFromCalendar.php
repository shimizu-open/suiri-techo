<?php

namespace App\Jobs;

use App\Contracts\CalendarApi;
use App\Models\CalendarConnection;
use App\Services\Google\Exceptions\GoogleAuthBroken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 消したタスクの予定を、Google カレンダーの専用カレンダーから消す後回しの仕事。
 * タスクはもう表にないので、持ち主の id と予定の ID だけを覚えて積む（TaskObserver が出す）。
 * もう消えている予定なら何もしない。Google が止まっていれば試し直し、許可が切れていたらあきらめる
 */
class RemoveTaskFromCalendar implements ShouldQueue
{
    use Queueable;

    /** 持ち主の id と、消す予定の ID を覚えて作る */
    public function __construct(public int $userId, public string $eventId) {}

    /** 試す回数（config） */
    public function tries(): int
    {
        return config('suiri.calendar.tries');
    }

    /**
     * 失敗したとき次に試すまで待つ秒数（config）
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return config('suiri.calendar.backoff');
    }

    /** 持ち主の専用カレンダーから予定を消す */
    public function handle(CalendarApi $api): void
    {
        $connection = CalendarConnection::where('user_id', $this->userId)->first();

        // 持ち主がつないでいない・切れた・専用カレンダーがないなら、何もしない
        if ($connection === null || ! $connection->isActive() || blank($connection->app_calendar_id)) {
            return;
        }

        // 予定を消す。許可が切れていたら（連携は broken になっている）試し直さずに終える
        try {
            $api->deleteEvent($connection, $connection->app_calendar_id, $this->eventId);
        } catch (GoogleAuthBroken) {
            return;
        }
    }

    /** 決めた回数を試してもだめだったときの記録。書くのは持ち主の id とエラーの種類だけ */
    public function failed(Throwable $e): void
    {
        Log::warning('カレンダーの予定を消すのをあきらめた', ['user_id' => $this->userId, 'error' => class_basename($e)]);
    }
}
