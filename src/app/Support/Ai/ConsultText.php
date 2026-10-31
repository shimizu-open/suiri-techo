<?php

namespace App\Support\Ai;

use App\Enums\Fit;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Queries\Ai\PredictionSheet;
use Illuminate\Support\Carbon;

/**
 * 事件の画面の「AI に相談する用に写す」で写す文を組み立てる係（<x-case.ai-copy> が使う）。
 * 中身：なぞ・手がかり（番号つき）・見立て（合う／合わない手がかりの番号・予想と答え合わせ）・わかったこと と、
 * 頼み方の文（正誤は判定せず、ほかの見立てと確かめ方の案を出して。名前などの個人情報は書かないで）。
 * 文は利用者の端末のクリップボードに写すだけで、アプリからはどこにも送らない。相棒モードで見せ合う前は見立てを写さない。
 * 事件は画面と同じく、手がかり・見立て（○×と予想つき）を読み込み済みのものを受け取る（読み込んでいなければここで読む）
 */
class ConsultText
{
    /** 写す文を返す */
    public static function for(CaseFile $case): string
    {
        $t = config('ai_connect.copy');
        $case->loadMissing(['clues', 'hypotheses.fits', 'hypotheses.predictions']);
        $numbers = $case->clues->pluck('id')->flip()->map(fn (int $index) => $index + 1); // 手がかりの id → 1 から始まる番号

        $lines = [$t['intro'], '', $t['title'], $case->title, '', $t['clues']];
        // 手がかりを1つずつ「1. ［見た］…」の形にする。1つもなければ「まだありません」
        foreach ($case->clues as $clue) {
            $lines[] = $numbers[$clue->id].'. ［'.$clue->kind->label().'］'.$clue->body;
        }
        $lines = [...$lines, ...($case->clues->isEmpty() ? [$t['none']] : []), '', $t['hypotheses'], ...self::hypotheses($case, $numbers->all())];

        // 閉じた事件で、わかったこと（結論）が書いてあれば足す
        if (filled($case->conclusion)) {
            $lines = [...$lines, '', $t['conclusion'], $case->conclusion];
        }

        return implode("\n", [...$lines, '', $t['request_title'], $t['request']]);
    }

    /**
     * 見立ての行を返す。相棒モードで見せ合う前なら「写していません」の一行、1つもなければ「まだありません」
     *
     * @param  array<int, int>  $numbers  手がかりの id → 番号
     * @return array<int, string>
     */
    private static function hypotheses(CaseFile $case, array $numbers): array
    {
        $t = config('ai_connect.copy');

        // 相棒モードで見せ合う前は、どちらの見立ても写さない（相手に見せない決まり）
        if ($case->hidesHypotheses()) {
            return [$t['hidden']];
        }
        // 見立てが1つもなければ「まだありません」
        if ($case->hypotheses->isEmpty()) {
            return [$t['none']];
        }

        return $case->hypotheses->flatMap(fn (Hypothesis $h) => [
            '・'.$h->body.'（'.$h->status->label().'）',
            ...self::fitLines($h, $numbers),
            ...$h->predictions->sortBy('id')->map(fn (Prediction $p) => '　'.self::prediction($p))->all(),
        ])->values()->all();
    }

    /**
     * 合う・合わない手がかりの番号の行を返す（○×が1つもなければ空）
     *
     * @param  array<int, int>  $numbers
     * @return array<int, string>
     */
    private static function fitLines(Hypothesis $hypothesis, array $numbers): array
    {
        $t = config('ai_connect.copy');
        $pick = fn (Fit $fit) => $hypothesis->fits->where('fit', $fit)->map(fn ($row) => $numbers[$row->clue_id] ?? null)->filter()->sort()->implode('・');
        $parts = array_filter([
            $pick(Fit::Fits) !== '' ? $t['fits'].$pick(Fit::Fits) : null,
            $pick(Fit::Contradicts) !== '' ? $t['contradicts'].$pick(Fit::Contradicts) : null,
        ]);

        return $parts === [] ? [] : ['　'.implode('　', $parts)];
    }

    /** 予想1つを「もしそうなら：…（確かさ 70%・確かめる日 11月3日）［答え合わせ待ち］」の形にする */
    private static function prediction(Prediction $prediction): string
    {
        $t = config('ai_connect.copy');
        $date = Carbon::parse($prediction->check_on)->isoFormat('M月D日');

        return $t['prediction'].$prediction->statement
            .'（'.strtr($t['confidence'], [':n' => $prediction->confidence]).'・'.strtr($t['check_on'], [':date' => $date]).'）'
            .'［'.PredictionSheet::state($prediction).'］';
    }
}
