<?php

namespace App\Queries\Cases;

use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 事件の詳細に出す「提案（AI なし）」をまとめて組み立てる係（requirements.md の 4-10 S-1〜S-5）。
 * 決まった型（StarterSuggestions）と、本人のこれまでの記録（屋根裏の棚・カレンダーの予定）から、入力欄の書き出しの候補を返す。
 * 押すと入力欄に入るだけで、保存は本人がする。閉じた事件では何も出さない（足す欄がないため）
 */
class SuggestionsQuery
{
    /** 型・屋根裏の棚・確かめる日の候補を作る係を受け取る */
    public function __construct(
        private StarterSuggestions $starters,
        private AtticSuggestionsQuery $attic,
        private CheckDateSuggestionsQuery $dates,
    ) {}

    /**
     * 画面に渡す候補を返す。$visible はいま見えている見立て（相棒モードで見せ合う前は、いまの番の人の見立て）、
     * $kid は子ども向けの言葉を出すか（ふだんの場面が親子の人）。
     * 返す形：clue（手がかりの問いかけ）・types（別の説明の型）・emphasize（型を目立たせるか）・attic（屋根裏の棚の候補）・
     * templates（見立ての id => 予想の穴埋めの文）・dates（確かめる日の候補）
     *
     * @param  Collection<int, Hypothesis>  $visible
     */
    public function for(CaseFile $case, User $user, Collection $visible, bool $kid, string $today): array
    {
        $empty = ['clue' => [], 'types' => [], 'emphasize' => false, 'attic' => [], 'templates' => [], 'dates' => []];
        // 閉じた事件には足す欄がないので、何も出さない（屋根裏の棚・カレンダーも読まない）
        if (! $case->isOpen()) {
            return $empty;
        }
        // 予想を立てる欄が出る見立て（見せ合ったあとの、消えていない見立て）
        $predictable = $case->hidesHypotheses() ? collect() : $visible->reject->isEliminated();

        return [
            'clue' => $this->starters->clueQuestions($case->scene),
            'types' => $this->starters->hypothesisTypes($case->scene, $kid),
            'emphasize' => $this->starters->emphasizeTypes($visible->count()),
            'attic' => $this->attic->for($case, $user),
            'templates' => $predictable->mapWithKeys(fn ($h) => [$h->id => $this->starters->predictionTemplate($h->body, $case->scene, $kid)])->all(),
            // 予想を立てる欄が1つもなければ、確かめる日の候補は要らない（カレンダーも読まない）
            'dates' => $predictable->isEmpty() ? [] : $this->dates->for($case, $user, $today),
        ];
    }
}
