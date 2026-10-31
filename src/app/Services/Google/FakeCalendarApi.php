<?php

namespace App\Services\Google;

use App\Contracts\CalendarApi as CalendarApiContract;
use App\Models\CalendarConnection;
use App\Services\Google\Exceptions\GoogleApiFailed;
use App\Services\Google\Exceptions\SyncTokenExpired;

/**
 * テスト用の Google カレンダー（偽物）。Google にはつながず、メモリに書くだけ。
 * テストのときは CalendarServiceProvider がこれを1つだけ作って結びつけるので、テストは app(CalendarApi::class) で中身を見られる。
 * - calendars・events：作った専用カレンダーと予定
 * - changes：次の listChangedEvents が返す予定。expiredSyncToken を true にすると、印つきの取り込みで 410 のしるしを出す
 * - agenda：listEvents が返す予定。failing を true にすると、すべての操作が GoogleApiFailed になる（Google が止まっている）
 */
class FakeCalendarApi implements CalendarApiContract
{
    /** @var array<string, string> カレンダーの ID => 名前 */
    public array $calendars = [];

    /** @var array<string, array<string, array<string, mixed>>> カレンダーの ID => [予定の ID => 予定の中身] */
    public array $events = [];

    /** @var list<array<string, mixed>> 次の取り込みで返す予定 */
    public array $changes = [];

    /** @var list<array<string, mixed>> 今日の予定として返す予定 */
    public array $agenda = [];

    /** @var list<?string> 取り込みで受け取った印（null は全件） */
    public array $syncTokensSeen = [];

    public bool $expiredSyncToken = false;

    public bool $failing = false;

    public int $calls = 0;

    /** 専用カレンダーを作ったことにして、決まった形の ID を返す */
    public function insertCalendar(CalendarConnection $connection, string $name, string $timeZone): string
    {
        $this->touch('insertCalendar');
        $id = 'fake-'.(count($this->calendars) + 1).'@group.calendar.google.com';
        $this->calendars[$id] = $name;

        return $id;
    }

    /** そのカレンダーを作ったことがあり、まだ消していなければ true */
    public function hasCalendar(CalendarConnection $connection, string $calendarId): bool
    {
        $this->touch('hasCalendar');

        return isset($this->calendars[$calendarId]);
    }

    /** カレンダーとその予定を、メモリから消す */
    public function deleteCalendar(CalendarConnection $connection, string $calendarId): void
    {
        $this->touch('deleteCalendar');
        unset($this->calendars[$calendarId], $this->events[$calendarId]);
    }

    /** 予定を書く（同じ ID なら上書き。二重にはならない） */
    public function upsertEvent(CalendarConnection $connection, string $calendarId, string $eventId, array $event): void
    {
        $this->touch('upsertEvent');
        $this->events[$calendarId][$eventId] = ['id' => $eventId] + $event;
    }

    /** 予定をメモリから消す */
    public function deleteEvent(CalendarConnection $connection, string $calendarId, string $eventId): void
    {
        $this->touch('deleteEvent');
        unset($this->events[$calendarId][$eventId]);
    }

    /** 用意しておいた変わった予定と、次の印を返す。印が古い設定なら、印つきのときだけ 410 のしるしを出す */
    public function listChangedEvents(CalendarConnection $connection, string $calendarId, ?string $syncToken): array
    {
        $this->touch('listChangedEvents');
        $this->syncTokensSeen[] = $syncToken;

        // 印が古くなった設定で、印つきで聞かれたら、全件を取り直す合図を出す
        if ($this->expiredSyncToken && $syncToken !== null) {
            throw new SyncTokenExpired;
        }

        return ['events' => $this->changes, 'sync_token' => 'fake-sync-'.count($this->syncTokensSeen)];
    }

    /** 用意しておいた今日の予定を返す */
    public function listEvents(CalendarConnection $connection, string $calendarId, string $timeMin, string $timeMax): array
    {
        $this->touch('listEvents');

        return $this->agenda;
    }

    /** 呼ばれた回数を数え、Google が止まっている設定なら失敗のしるしを出す */
    private function touch(string $operation): void
    {
        $this->calls++;

        // Google が止まっている設定なら、どの操作も失敗にする
        if ($this->failing) {
            throw new GoogleApiFailed($operation, 503);
        }
    }
}
