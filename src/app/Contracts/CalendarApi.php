<?php

namespace App\Contracts;

use App\Models\CalendarConnection;

/**
 * Google カレンダーの API を使う窓口。本物（App\Services\Google\CalendarApi）は Laravel の HTTP クライアントで REST を直接呼び、
 * テストでは偽物（App\Services\Google\FakeCalendarApi。メモリに書くだけ）に差し替える。
 * どのメソッドも、利用者の連携（トークンの持ち主）を最初に受け取る。
 * 失敗したら GoogleApiFailed、許可が取り消された・切れたら GoogleAuthBroken を投げる（App\Services\Google\Exceptions）
 */
interface CalendarApi
{
    /** 専用カレンダーを作り、その ID を返す（POST /calendars） */
    public function insertCalendar(CalendarConnection $connection, string $name, string $timeZone): string;

    /** そのカレンダーがまだあるかを返す（GET /calendars/{id}。消されていれば false） */
    public function hasCalendar(CalendarConnection $connection, string $calendarId): bool;

    /** 専用カレンダーを消す（DELETE /calendars/{id}。すでに消えていれば何もしない） */
    public function deleteCalendar(CalendarConnection $connection, string $calendarId): void;

    /**
     * 予定を作る。同じ ID の予定がもうあれば（409）中身を置き換える（insert → update）。二重登録を防ぐ
     *
     * @param  array<string, mixed>  $event  題名・日時・リンクとタスクの番号だけの予定の中身（App\Services\Google\TaskEvent が作る）
     */
    public function upsertEvent(CalendarConnection $connection, string $calendarId, string $eventId, array $event): void;

    /** 予定を消す（すでに消えていれば何もしない） */
    public function deleteEvent(CalendarConnection $connection, string $calendarId, string $eventId): void;

    /**
     * 前の取り込みから変わった予定を返す。syncToken が null なら全件。
     * 印（syncToken）が古くなっていれば（410）SyncTokenExpired を投げる（全件を取り直す合図）
     *
     * @return array{events: list<array<string, mixed>>, sync_token: ?string}
     */
    public function listChangedEvents(CalendarConnection $connection, string $calendarId, ?string $syncToken): array;

    /**
     * その時間の範囲にある予定を開始の順に返す（繰り返しの予定は1回ずつに分ける）
     *
     * @return list<array<string, mixed>>
     */
    public function listEvents(CalendarConnection $connection, string $calendarId, string $timeMin, string $timeMax): array;
}
