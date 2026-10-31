<?php

namespace App\Queries\Cases;

use App\Models\AtticNote;
use App\Models\CaseFile;
use App\Models\User;
use App\Support\WordOverlap;
use Illuminate\Support\Str;

/**
 * 屋根裏の棚からの提案（S-5）を選ぶ係。本人の棚の知識（つまり？）のうち、事件のなぞ・手がかりと言葉が重なるものを数件返す。
 * 重なりの判定は App\Support\WordOverlap（漢字2文字・カタカナと英数字3文字のかけらが同じか）。
 * 見るのは本人の棚だけ（$user->atticNotes()）。この事件から生まれた知識は「前にわかったこと」ではないので出さない
 */
class AtticSuggestionsQuery
{
    /** 書き出しを作る係を受け取る */
    public function __construct(private StarterSuggestions $starters) {}

    /**
     * 重なりの多い順（同じなら新しい順）に、決まった件数まで返す。1件：id・rule（つまり？）・label（ボタンの言葉「前にわかったこと：〜」）・
     * text（見立ての欄に入れる書き出し）・attic（予想の欄で「使う知識」に選ぶ id）。重なるものがなければ空
     *
     * @return list<array{id: int, rule: string, label: string, text: string, attic: int}>
     */
    public function for(CaseFile $case, User $user): array
    {
        $grams = WordOverlap::grams($case->title, ...$case->clues->pluck('body')->all());
        // なぞと手がかりに言葉のかけらがなければ、比べようがないので何も出さない
        if ($grams === []) {
            return [];
        }

        // 棚の全部の知識を、新しい順に少しずつ読んで比べ、重なりの多い上位だけを手元に残す（古い知識も候補から外さない）
        $top = $user->atticNotes()
            ->where(fn ($query) => $query->whereNull('case_file_id')->orWhere('case_file_id', '!=', $case->id))
            ->select(['id', 'rule'])
            ->lazyByIdDesc((int) config('suiri.suggest.attic.chunk'))
            ->map(fn (AtticNote $note) => ['note' => $note, 'score' => WordOverlap::score($grams, $note->rule)])
            ->filter(fn (array $pair) => $pair['score'] > 0)
            ->reduce(fn (array $kept, array $pair) => $this->keepBest([...$kept, $pair]), []);

        return collect($top)
            ->map(fn (array $pair) => [
                'id' => $pair['note']->id,
                'rule' => $pair['note']->rule,
                'label' => config('suiri.suggest.attic.label').Str::limit($pair['note']->rule, (int) config('suiri.suggest.quote_length'), '…'),
                'text' => $this->starters->atticStarter($pair['note']->rule),
                'attic' => $pair['note']->id,
            ])
            ->values()
            ->all();
    }

    /**
     * 比べた知識のうち、重なりの多い順に決まった件数だけを残す。重なりが同じなら先に来たもの（新しい知識）を前にする
     *
     * @param  list<array{note: AtticNote, score: int}>  $pairs  前に残した上位と、いま比べた知識（後ろほど古い知識）
     * @return list<array{note: AtticNote, score: int}>
     */
    private function keepBest(array $pairs): array
    {
        return collect($pairs)->sortByDesc('score')->take((int) config('suiri.suggest.attic.limit'))->values()->all();
    }
}
