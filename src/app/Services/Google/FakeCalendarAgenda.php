<?php

namespace App\Services\Google;

use App\Contracts\CalendarAgenda;
use App\Models\User;

/**
 * テスト用の予定の窓口（偽物）。Google にはつながず、用意した予定をそのまま返す。
 * テストで $this->app->instance(CalendarAgenda::class, new FakeCalendarAgenda(...)) と結びつけて使う。
 * - today：eventsOn が返す今日の予定  - upcoming：upcoming が返すこれからの予定
 * - upcomingCalls：upcoming を呼ばれた回数（つながっていない人に問い合わせていないかを確かめる）
 */
class FakeCalendarAgenda implements CalendarAgenda
{
    public int $upcomingCalls = 0;

    /**
     * 返す予定を受け取る
     *
     * @param  list<array<string, mixed>>  $today
     * @param  list<array<string, mixed>>  $upcoming
     */
    public function __construct(public array $today = [], public array $upcoming = []) {}

    /** 用意した今日の予定を返す */
    public function eventsOn(User $user, string $date): array
    {
        return $this->today;
    }

    /** 用意したこれからの予定を返し、呼ばれた回数を数える */
    public function upcoming(User $user, int $days): array
    {
        $this->upcomingCalls++;

        return $this->upcoming;
    }
}
