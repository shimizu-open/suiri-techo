<?php

namespace App\Queries\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * タスクの一覧を、今日／これから／いつか／済み に振り分けて組み立てる係（いつもその人のタスクだけ）。
 * - 今日：期日が今日までの、まだ済んでいないもの（期日を過ぎたものも忘れないよう、古い順に上へ）
 * - これから：期日が明日より先の、まだ済んでいないもの
 * - いつか：期日のない、まだ済んでいないもの
 * - 済み：済んだもの（新しい順に config の件数まで）
 * 日付だけの due_on は 'Y-m-d' の文字列で比べる。開始時刻のない（終日の）ものは、同じ日の中で上に来る（null は小さい扱い）
 */
class TaskListQuery
{
    /** 送られた ?view= の値を、知っている一覧の種類にそろえる（知らない値なら「今日」） */
    public static function normalize(?string $view): string
    {
        return array_key_exists((string) $view, config('suiri.tasks.views')) ? $view : 'today';
    }

    /** タスクがどの一覧に入るかを返す（保存したあとに、そのタスクが見える一覧へ戻すため） */
    public static function viewOf(Task $task, string $today): string
    {
        // 済んでいれば「済み」、期日がなければ「いつか」、期日が今日までなら「今日」、それより先なら「これから」
        return match (true) {
            $task->isDone() => 'done',
            $task->due_on === null => 'someday',
            $task->due_on <= $today => 'today',
            default => 'upcoming',
        };
    }

    /** その種類の一覧のタスクを、並べる順に返す */
    public function list(User $user, string $view, string $today): Collection
    {
        $tasks = $user->tasks();

        // 種類ごとに、絞り方と並べ方を変える
        return match ($view) {
            'done' => $tasks->done()->latest('done_at')->latest('id')->limit(config('suiri.tasks.done_limit'))->get(),
            'someday' => $tasks->open()->whereNull('due_on')->orderBy('id')->get(),
            'upcoming' => $this->byDate($tasks->open()->where('due_on', '>', $today))->get(),
            default => $this->byDate($tasks->open()->where('due_on', '<=', $today))->get(),
        };
    }

    /** 今日・これから・いつか の件数を返す（タブに出す。済みは数が増え続けるので数えない） */
    public function counts(User $user, string $today): array
    {
        return [
            'today' => $user->tasks()->open()->where('due_on', '<=', $today)->count(),
            'upcoming' => $user->tasks()->open()->where('due_on', '>', $today)->count(),
            'someday' => $user->tasks()->open()->whereNull('due_on')->count(),
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
