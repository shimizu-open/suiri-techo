<?php

namespace App\Queries\Cases;

use App\Contracts\CalendarAgenda;
use App\Models\CaseFile;
use App\Models\User;
use App\Support\WordOverlap;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * 確かめる日の提案（S-4）を組み立てる係。
 * ①「明日」「3日後」「1週間後」のすぐ選べる日と、②カレンダーとつないでいる人には、これから決まった日数の自分の予定のうち、
 * 事件のなぞ・手がかり・見立ての言葉と題名が重なる予定（App\Support\WordOverlap）を「次の『◯◯』の日」として数件返す。
 * 予定の題名は画面に出すだけで、どこにも保存しない（読むのは CalendarAgenda::upcoming。題名と時刻だけ・数分だけ覚える）
 */
class CheckDateSuggestionsQuery
{
    /** 予定の窓口を受け取る（テストでは偽物に差し替える） */
    public function __construct(private CalendarAgenda $calendar) {}

    /**
     * 確かめる日の候補を返す。1件：label（ボタンの言葉）・date（Y-m-d）・time（HH:MM か null）・from（quick か calendar）
     *
     * @return list<array{label: string, date: string, time: ?string, from: string}>
     */
    public function for(CaseFile $case, User $user, string $today): array
    {
        $base = Carbon::parse($today);
        $quick = array_map(fn (array $q) => [
            'label' => $q['label'],
            'date' => $base->copy()->addDays($q['days'])->toDateString(),
            'time' => null,
            'from' => 'quick',
        ], config('suiri.suggest.check_dates.quick'));

        return [...$quick, ...$this->fromCalendar($case, $user)];
    }

    /**
     * 事件の言葉と題名が重なる、これからの予定を「次の『◯◯』の日」にして返す。同じ題名はいちばん近い日だけ。
     * 重なりの多い順に決まった件数を選び、日付の順に並べる。カレンダーとつないでいない人には何も問い合わせない
     *
     * @return list<array{label: string, date: string, time: ?string, from: string}>
     */
    private function fromCalendar(CaseFile $case, User $user): array
    {
        $alive = $case->hypotheses->filter(fn ($h) => ! $h->isEliminated())->pluck('body')->all();
        $grams = WordOverlap::grams($case->title, ...$case->clues->pluck('body')->all(), ...$alive);
        // カレンダーとつないでいない・切れた人、事件に言葉のかけらがないときは、予定を読まずに何も出さない
        if (! $user->calendarConnection?->isActive() || $grams === []) {
            return [];
        }

        return collect($this->calendar->upcoming($user, (int) config('suiri.suggest.check_dates.calendar_days')))
            ->map(fn (array $event) => $event + ['score' => WordOverlap::score($grams, $event['title'])])
            ->filter(fn (array $event) => $event['score'] > 0)
            ->sortBy(fn (array $event) => $event['date'].($event['starts_at'] ?? ''))
            ->unique('title')
            ->sortByDesc('score')
            ->take(config('suiri.suggest.check_dates.calendar_limit'))
            ->sortBy(fn (array $event) => $event['date'].($event['starts_at'] ?? ''))
            ->map(fn (array $event) => [
                'label' => str_replace(
                    [':title', ':date'],
                    [Str::limit($event['title'], (int) config('suiri.suggest.check_dates.title_length'), '…'), Carbon::parse($event['date'])->isoFormat('M/D ddd')],
                    config('suiri.suggest.check_dates.calendar_label'),
                ),
                'date' => $event['date'],
                'time' => $event['all_day'] ? null : $event['starts_at'],
                'from' => 'calendar',
            ])
            ->values()
            ->all();
    }
}
