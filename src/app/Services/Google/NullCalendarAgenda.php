<?php

namespace App\Services\Google;

use App\Contracts\CalendarAgenda;
use App\Models\User;

/**
 * カレンダーとつながっていないときの予定の窓口。いつも空を返す。
 * カレンダーの担当が本物の実装を作るまでの仮の結びつけにも使う
 */
class NullCalendarAgenda implements CalendarAgenda
{
    /** 予定はないものとして空の配列を返す */
    public function eventsOn(User $user, string $date): array
    {
        return [];
    }

    /** これからの予定もないものとして空の配列を返す */
    public function upcoming(User $user, int $days): array
    {
        return [];
    }
}
