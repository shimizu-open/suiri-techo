<?php

namespace App\Services\Google;

use App\Contracts\CalendarAgenda;
use App\Contracts\CalendarApi;
use App\Models\CalendarConnection;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 「自分の Google カレンダーの予定」を返す係（CalendarAgenda の本物）。今日の画面の予定と、確かめる日の提案に使うこれからの予定。
 * ふだんのカレンダー（primary）を読み、表に保存せず数分だけ覚えておく（受け取るのは題名と日時だけ）。
 * 専用カレンダー「推理手帳」の予定はタスクとして別に出るので入れない。つながっていない・失敗したときは空を返す
 */
class GoogleCalendarAgenda implements CalendarAgenda
{
    /** Google カレンダーの窓口を受け取る */
    public function __construct(private CalendarApi $api) {}

    /** その日の予定を時刻順に返す（終日の予定が先）。読めないときは空の配列 */
    public function eventsOn(User $user, string $date): array
    {
        return $this->remember($user, "calendar-agenda:%d:{$date}", fn (CalendarConnection $c) => $this->fetch($c, $date));
    }

    /**
     * いまから $days 日先までの予定を、日付と時刻の順に返す（すでに始まった時刻つきの予定は入れない）。読めないときは空の配列。
     * 覚えておいた予定から返すときも、その間に始まった予定を外す（覚えるのは数分なので、その間に始まる予定がある）
     */
    public function upcoming(User $user, int $days): array
    {
        $now = CarbonImmutable::now(config('suiri.calendar.timezone'));
        $items = $this->remember($user, "calendar-upcoming:%d:{$days}:{$now->format('Y-m-d')}", fn (CalendarConnection $c) => $this->fetchUpcoming($c, $now, $days));

        return $this->notStarted($items, $now);
    }

    /**
     * 提案の形の予定から、いまの時刻までに始まった時刻つきの予定を外す（終日の予定は残す）
     *
     * @param  list<array{title: string, date: string, starts_at: ?string, all_day: bool}>  $items
     * @return list<array{title: string, date: string, starts_at: ?string, all_day: bool}>
     */
    private function notStarted(array $items, CarbonImmutable $now): array
    {
        return collect($items)
            ->reject(fn (array $item) => ! $item['all_day'] && CarbonImmutable::parse("{$item['date']} {$item['starts_at']}", $now->timezone)->lt($now))
            ->values()
            ->all();
    }

    /**
     * つながっている人の予定を、数分は覚えたものから返す。$key の %d には連携の id が入る。
     * 予定を読む許可がない人・失敗したときは空を返す（画面を止めない。失敗は覚えない）
     *
     * @param  Closure(CalendarConnection): array  $fetch  Google から読む処理
     */
    private function remember(User $user, string $key, Closure $fetch): array
    {
        $connection = $user->calendarConnection;

        // つないでいない・切れた・予定を読む許可がない人は、Google に聞かずに空を返す
        if ($connection === null || ! $connection->isActive() || ! $connection->hasScope(config('suiri.calendar.read_scope'))) {
            return [];
        }

        // Google から読む（数分は覚えたものを使う）。失敗したら画面を止めないよう空を返す（失敗は覚えない）
        try {
            return Cache::remember(sprintf($key, $connection->id), config('suiri.calendar.agenda_cache_seconds'), fn () => $fetch($connection));
        } catch (Throwable $e) {
            Log::info('カレンダーの予定を読めなかった', ['connection_id' => $connection->id, 'error' => class_basename($e)]);

            return [];
        }
    }

    /**
     * ふだんのカレンダーから、いまから $days 日先（その日の終わり）までの予定を1回で読み、提案の形（題名・日付・時刻）にそろえて並べる
     *
     * @return list<array{title: string, date: string, starts_at: ?string, all_day: bool}>
     */
    private function fetchUpcoming(CalendarConnection $connection, CarbonImmutable $now, int $days): array
    {
        $events = $this->api->listEvents($connection, 'primary', $now->toRfc3339String(), $now->startOfDay()->addDays($days + 1)->toRfc3339String());

        // アプリが書いた予定を除き、提案の形にして、日付 → 終日 → 時刻の順に並べる（始まった予定は、返すときに upcoming() で外す）
        return collect($events)
            ->reject(fn (array $event) => isset($event['extendedProperties']['private']['suiri_task_id']))
            ->map(fn (array $event) => $this->upcomingItem($event))
            ->sortBy(fn (array $item) => $item['date'].($item['all_day'] ? '0' : '1').($item['starts_at'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * 予定1件を提案の形（題名・日付・開始の時刻・終日か）にする。日付と時刻はアプリのタイムゾーン
     *
     * @param  array<string, mixed>  $event
     * @return array{title: string, date: string, starts_at: ?string, all_day: bool}
     */
    private function upcomingItem(array $event): array
    {
        $title = filled($event['summary'] ?? null) ? $event['summary'] : config('suiri.calendar.untitled');

        // 日付だけの予定は終日として、時刻なしで返す
        if (! isset($event['start']['dateTime'])) {
            return ['title' => $title, 'date' => (string) ($event['start']['date'] ?? ''), 'starts_at' => null, 'all_day' => true];
        }
        $start = CarbonImmutable::parse($event['start']['dateTime'])->setTimezone(config('suiri.calendar.timezone'));

        return ['title' => $title, 'date' => $start->format('Y-m-d'), 'starts_at' => $start->format('H:i'), 'all_day' => false];
    }

    /**
     * ふだんのカレンダーからその日の予定を読み、今日の画面の形にそろえて並べる
     *
     * @return list<array{title: string, starts_at: ?string, ends_at: ?string, all_day: bool}>
     */
    private function fetch(CalendarConnection $connection, string $date): array
    {
        $dayStart = CarbonImmutable::parse($date, config('suiri.calendar.timezone'))->startOfDay();
        $events = $this->api->listEvents($connection, 'primary', $dayStart->toRfc3339String(), $dayStart->addDay()->toRfc3339String());

        // アプリが書いた予定（タスクとして別に出る）を除き、今日の画面の形にして、終日 → 時刻の早い順に並べる
        return collect($events)
            ->reject(fn (array $event) => isset($event['extendedProperties']['private']['suiri_task_id']))
            ->map(fn (array $event) => $this->item($event, $dayStart))
            ->sortBy(fn (array $item) => ($item['all_day'] ? '0' : '1').($item['starts_at'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * 予定1件を今日の画面の形（題名・開始・終わり・終日か）にする。時刻はアプリのタイムゾーンの HH:MM。
     * 前の日から続く予定は 00:00 から、次の日まで続く予定は終わりを出さない
     *
     * @param  array<string, mixed>  $event
     * @return array{title: string, starts_at: ?string, ends_at: ?string, all_day: bool}
     */
    private function item(array $event, CarbonImmutable $dayStart): array
    {
        $title = filled($event['summary'] ?? null) ? $event['summary'] : config('suiri.calendar.untitled');

        // 日付だけの予定は終日として、時刻なしで返す
        if (! isset($event['start']['dateTime'])) {
            return ['title' => $title, 'starts_at' => null, 'ends_at' => null, 'all_day' => true];
        }

        $timeZone = config('suiri.calendar.timezone');
        $start = CarbonImmutable::parse($event['start']['dateTime'])->setTimezone($timeZone);
        $end = isset($event['end']['dateTime']) ? CarbonImmutable::parse($event['end']['dateTime'])->setTimezone($timeZone) : null;

        return [
            'title' => $title,
            'starts_at' => $start->max($dayStart)->format('H:i'),
            'ends_at' => ($end !== null && $end->lt($dayStart->addDay())) ? $end->format('H:i') : null,
            'all_day' => false,
        ];
    }
}
