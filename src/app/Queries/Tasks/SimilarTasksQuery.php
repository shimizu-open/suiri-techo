<?php

namespace App\Queries\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 「題名の似た過去のタスクの実績」を探す係（見積もりの答え合わせ。予想時間の欄の近くに出す）。
 * その人の済んだタスクのうち、かかった時間が入っているものから、題名の似たものを数件返す。
 * 似ているかの判定は単純：空白と記号を取って比べ、同じ題名・片方がもう片方を含む・2文字ずつの組がよく重なる（ダイス係数）なら似ている。
 * 日本語は単語の区切りがないので、2文字ずつの組で比べる（「買い物」と「買い物に行く」は似ている、「牛乳を買う」とは似ていない）
 */
class SimilarTasksQuery
{
    /** 片方の題名がもう片方を含むときの似ている度合い */
    private const CONTAINS_SCORE = 0.8;

    /**
     * 題名の似た、済んだタスクを返す。似ている順（同じなら新しい順）に決まった件数を選び、見せるときは新しい順（先頭が「前回」）。
     * 題名が空なら空のコレクション
     *
     * @return Collection<int, Task>
     */
    public function for(User $user, ?string $title, ?int $exceptId = null): Collection
    {
        $key = self::normalize((string) $title);
        // 題名がなければ比べようがないので、何も返さない
        if ($key === '') {
            return collect();
        }
        $minScore = (float) config('suiri.tasks.similar.min_score');

        return $user->tasks()->done()->whereNotNull('actual_minutes')
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->latest('done_at')->latest('id')->limit(config('suiri.tasks.similar.pool'))
            ->get(['id', 'title', 'duration_minutes', 'actual_minutes', 'done_at'])
            ->map(fn (Task $task) => ['task' => $task, 'score' => self::score($key, self::normalize($task->title))])
            ->filter(fn (array $pair) => $pair['score'] >= $minScore)
            ->sortByDesc('score')
            ->take(config('suiri.tasks.similar.limit'))
            ->pluck('task')
            ->sortByDesc(fn (Task $task) => $task->done_at->getTimestamp())
            ->values();
    }

    /** 比べやすいように、題名から空白・記号を取り、英字を小文字にそろえる */
    private static function normalize(string $title): string
    {
        return mb_strtolower((string) preg_replace('/[\s\p{P}\p{S}]+/u', '', $title));
    }

    /** 2つの題名（そろえたもの）の似ている度合いを 0〜1 で返す */
    private static function score(string $a, string $b): float
    {
        // 同じ題名なら、いちばん似ている
        if ($a === $b) {
            return 1.0;
        }
        // 相手が空（記号だけの題名）なら、似ていない
        if ($b === '') {
            return 0.0;
        }
        $x = self::bigrams($a);
        $y = self::bigrams($b);
        $dice = 2 * count(array_intersect_key($x, $y)) / (count($x) + count($y));
        $contains = mb_strlen($a) >= 2 && mb_strlen($b) >= 2 && (str_contains($a, $b) || str_contains($b, $a));

        return max($dice, $contains ? self::CONTAINS_SCORE : 0.0);
    }

    /** 文字列を2文字ずつの組（重なりを数えやすいよう、組をキーにした配列）にする。1文字ならその1文字 */
    private static function bigrams(string $text): array
    {
        $chars = mb_str_split($text);
        // 1文字しかなければ、組が作れないのでその文字だけを返す
        if (count($chars) < 2) {
            return [$text => true];
        }
        $pairs = [];
        // となり合う2文字を1組ずつ取り出して、組の一覧に入れる（最後の1つ手前まで）
        for ($i = 0; $i < count($chars) - 1; $i++) {
            $pairs[$chars[$i].$chars[$i + 1]] = true;
        }

        return $pairs;
    }
}
