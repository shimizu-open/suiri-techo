<?php

namespace App\Queries\Tasks;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * きょうの画面に出すタスクを、枠ごとに振り分けて組み立てる係（いつもその人のタスクだけ）。
 * - 期日を過ぎた（overdue）：期日が今日より前の、まだ済んでいないもの（古い順。予想に結びついたものも入れる。印は画面が出す）
 * - これから（upcoming）：期日が明日より先の、まだ済んでいないもの全部（日付ごとにまとめる）。先の日付でも見えなくならないよう、日数で区切らない
 * - いつか（someday）：期日のない、まだ済んでいないもの（作った順）
 * - 済み（done）：今日を入れて config('suiri.today_tasks.done_days') 日のあいだに済んだもの（新しい順に config の件数まで）
 * 今日が期日のものは、予定と混ぜて Today\AgendaQuery が並べる。
 * 日付だけの due_on は 'Y-m-d' の文字列で比べる。開始時刻のない（終日の）ものは、同じ日の中で上に来る（null は小さい扱い）
 */
class TaskListQuery
{
    /** きょうの画面のたたんだ枠の名前（?open= で開ける値。前の一覧の ?view= の値と同じ名前） */
    public const OPENS = ['upcoming', 'someday', 'done'];

    /** 送られた ?open=（前の一覧なら ?view=）の値を、たたんだ枠の名前にそろえる（知らない値・「today」・空なら null＝どれも開かない） */
    public static function normalizeOpen(mixed $open): ?string
    {
        return in_array($open, self::OPENS, true) ? $open : null;
    }

    /** タスクがきょうの画面のどの枠に入るかを返す（保存したあとに、その枠を開いて戻すため。今日までのものは上に出るので null） */
    public static function openOf(Task $task, string $today): ?string
    {
        // 済んでいれば「済み」、期日がなければ「いつか」、期日が明日より先なら「これから」、今日まで（今日の並び・期日を過ぎた）なら開く枠なし
        return match (true) {
            $task->isDone() => 'done',
            $task->due_on === null => 'someday',
            $task->due_on > $today => 'upcoming',
            default => null,
        };
    }

    /**
     * きょうの画面のタスクの枠と件数、1行で足す欄のボタンの日付をまとめて返す（$today は 'Y-m-d'）。
     * 形：overdue・someday・done は Task の Collection、upcoming は 'Y-m-d' => Task の Collection にまとめた Collection、
     * counts は ['upcoming' => int, 'someday' => int, 'done' => int, 'overdue' => int]（upcoming は日数でなくタスクの数）、
     * dates は ['today' => 'Y-m-d', 'tomorrow' => 'Y-m-d']
     *
     * @return array<string, mixed>
     */
    public function forToday(User $user, string $today): array
    {
        $day = Carbon::parse($today);
        $tomorrow = $day->copy()->addDay()->toDateString();
        $doneFrom = $day->copy()->subDays((int) config('suiri.today_tasks.done_days') - 1)->startOfDay();

        $overdue = $this->byDate($user->tasks()->open()->where('due_on', '<', $today))->get();
        $upcoming = $this->byDate($user->tasks()->open()->where('due_on', '>=', $tomorrow))->get();
        $someday = $user->tasks()->open()->whereNull('due_on')->orderBy('id')->get();
        $done = $user->tasks()->done()->where('done_at', '>=', $doneFrom)
            ->latest('done_at')->latest('id')->limit((int) config('suiri.today_tasks.done_limit'))->get();

        return [
            'overdue' => $overdue,
            'upcoming' => $upcoming->groupBy('due_on'),
            'someday' => $someday,
            'done' => $done,
            'counts' => [
                'upcoming' => $upcoming->count(),
                'someday' => $someday->count(),
                'done' => $done->count(),
                'overdue' => $overdue->count(),
            ],
            'dates' => ['today' => $today, 'tomorrow' => $tomorrow],
        ];
    }

    /** 済みにした直後のタスクを、その人のものの中から探して返す（かかった時間と「気になる」をさそう枠に使う） */
    public function justDone(User $user, mixed $id): ?Task
    {
        // 済みにした直後でなければ（お知らせに id がなければ）、さそう枠は出さない
        if (! is_numeric($id)) {
            return null;
        }

        return $user->tasks()->done()->find((int) $id);
    }

    /** 期日の古い順 → 同じ日なら終日を先に、時刻の早い順 → 作った順、に並べる */
    private function byDate(HasMany|Builder $query): HasMany|Builder
    {
        return $query->orderBy('due_on')->orderBy('starts_at')->orderBy('id');
    }
}
