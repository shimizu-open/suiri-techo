<?php

namespace App\Contracts;

use App\Models\User;

/**
 * 「自分の Google カレンダーの予定」を返す窓口。今日の画面の予定（eventsOn）と、確かめる日の提案に使うこれからの予定（upcoming）。
 * 本物は Services/Google/GoogleCalendarAgenda、つながっていないときの仮は NullCalendarAgenda、テストの偽物は FakeCalendarAgenda。
 * 受け取るのは題名と時刻だけで、表には保存しない。つながっていない人・失敗したときは空の配列を返す（画面を止めない）
 */
interface CalendarAgenda
{
    /**
     * その日の予定を時刻順に返す。1件の形：
     * ['title' => string, 'starts_at' => ?string（HH:MM。終日なら null）, 'ends_at' => ?string, 'all_day' => bool]
     *
     * @return list<array{title: string, starts_at: ?string, ends_at: ?string, all_day: bool}>
     */
    public function eventsOn(User $user, string $date): array;

    /**
     * いまから $days 日先（その日の終わり）までの予定を、日付と時刻の順に返す（Google への問い合わせは1回・数分だけ覚える）。
     * 確かめる日の提案（S-4）に使う。題名は画面に出すだけで保存しない。1件の形：
     * ['title' => string, 'date' => string（Y-m-d）, 'starts_at' => ?string（HH:MM。終日なら null）, 'all_day' => bool]
     *
     * @return list<array{title: string, date: string, starts_at: ?string, all_day: bool}>
     */
    public function upcoming(User $user, int $days): array;
}
