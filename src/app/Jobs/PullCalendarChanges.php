<?php

namespace App\Jobs;

use App\Actions\Calendar\ApplyCalendarChanges;
use App\Enums\CalendarStatus;
use App\Models\CalendarConnection;
use App\Services\Google\Exceptions\GoogleAuthBroken;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 専用カレンダーで動かされた・消された予定を、タスクに取り込む後回しの仕事（routes/console.php の予定で15分ごと）。
 * 連携を指定せずに出すと、つながっている人ごとに1つずつこの仕事を出し直す（1人が失敗しても、ほかの人は取り込める）。
 * 連携を指定して出すと、その人の分を ApplyCalendarChanges で取り込む（消された予定は ID だけの知らせでもつながりを外し、
 * 印が切れた（410）ときの全件の読み直しでは、Google にもうない予定とのつながりも外してから新しい印を保存する）
 */
class PullCalendarChanges implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** 取り込む連携の id を覚えて作る（null なら、つながっている全員の分を出す係になる） */
    public function __construct(public ?int $connectionId = null) {}

    /** 同じ連携の取り込みは、待っている間は1つにまとめる */
    public function uniqueId(): string
    {
        return (string) ($this->connectionId ?? 'all');
    }

    /** まとめておく秒数（次の予定の時刻まで） */
    public function uniqueFor(): int
    {
        return config('suiri.calendar.pull_every_minutes') * 60;
    }

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

    /** 全員の分を出し直すか、1人分を取り込む（差分か全件かの判断と、つながりの外し方は ApplyCalendarChanges に任せる） */
    public function handle(ApplyCalendarChanges $apply): void
    {
        // 連携の指定がなければ、つながっていて専用カレンダーのある人ごとに、この仕事を出し直す
        if ($this->connectionId === null) {
            $this->dispatchEach();

            return;
        }

        $connection = CalendarConnection::find($this->connectionId);

        // 連携がもうない・切れた・専用カレンダーがないなら、何もしない
        if ($connection === null || ! $connection->isActive() || blank($connection->app_calendar_id)) {
            return;
        }

        // 取り込む。許可が切れていたら（連携は broken になっている）試し直さずに終える
        try {
            $apply->handle($connection);
        } catch (GoogleAuthBroken) {
            return;
        }
    }

    /** つながっていて専用カレンダーのある連携ごとに、取り込みの仕事を出す */
    private function dispatchEach(): void
    {
        $ids = CalendarConnection::where('status', CalendarStatus::Active)->whereNotNull('app_calendar_id')->pluck('id');

        // 連携の id を1つずつ取り出して、その人の取り込みの仕事を出す
        foreach ($ids as $id) {
            self::dispatch($id);
        }
    }
}
