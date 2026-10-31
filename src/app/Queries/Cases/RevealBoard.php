<?php

namespace App\Queries\Cases;

use App\Enums\Fit;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\Hypothesis;
use Illuminate\Support\Collection;

/**
 * 相棒モードで見せ合ったあとの「並べて見る」表を組み立てる係（C-11）。
 * 相棒と自分の見立てを左右に並べ、手がかりごとに、どちらがどの見立てで ○（合う）・×（合わない）を付けたかと、その違いを出す。
 * 勝ち負けではなく「どの手がかりで見方が分かれたか」を見せるためのもの。事件は clues・hypotheses.fits を読み込んで渡す
 */
class RevealBoard
{
    /**
     * 表の中身を返す：partner・self（それぞれの見立て。番号は1から）・rows（手がかりごとの印と、違いがあるか）
     *
     * @return array{partner: Collection, self: Collection, rows: array<int, array{clue: Clue, partner: array, self: array, differs: bool}>}
     */
    public function build(CaseFile $case): array
    {
        $partner = $case->hypotheses->filter->isByPartner()->values();
        $self = $case->hypotheses->reject->isByPartner()->values();

        $rows = [];
        // 手がかりを1つずつ取り出し、両方の側の印を集めて比べる
        foreach ($case->clues as $clue) {
            $p = $this->marks($partner, $clue->id);
            $s = $this->marks($self, $clue->id);
            $rows[] = [
                'clue' => $clue,
                'partner' => $p,
                'self' => $s,
                'differs' => (bool) $p['fits'] !== (bool) $s['fits'] || (bool) $p['contradicts'] !== (bool) $s['contradicts'],
            ];
        }

        return ['partner' => $partner, 'self' => $self, 'rows' => $rows];
    }

    /** 片方の側の見立てのうち、その手がかりに ○ を付けた番号と × を付けた番号を返す（番号は並びの1から） */
    private function marks(Collection $hypotheses, int $clueId): array
    {
        $marks = ['fits' => [], 'contradicts' => []];
        // 見立てを1つずつ取り出し、その手がかりへの印があれば番号を控える
        foreach ($hypotheses as $i => $hypothesis) {
            $fit = $this->fitOf($hypothesis, $clueId);
            // 印があれば、合う・合わないの側に番号を入れる
            if ($fit !== null) {
                $marks[$fit->value][] = $i + 1;
            }
        }

        return $marks;
    }

    /** 見立てがその手がかりに付けた印（なければ null）を返す */
    private function fitOf(Hypothesis $hypothesis, int $clueId): ?Fit
    {
        return $hypothesis->fits->firstWhere('clue_id', $clueId)?->fit;
    }
}
