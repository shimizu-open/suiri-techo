<?php

namespace App\Queries\Cases;

use App\Models\CaseFile;
use App\Models\User;
use App\Services\Watson\WatsonNotes;
use App\Support\Guide\GuideLibrary;

/**
 * 事件の画面（views/cases/board。いまやることのカード1枚）に出すものをまとめて組み立てる係。
 * 手がかり・見立て（○× と予想つき）を読み込み、相棒モードで見せ合う前なら「いまの番」の見立てだけに絞る。
 * いまの一手（NextStep）・ワトソンのひとこと（1つだけ）・見せ合いの表・予想で選べる屋根裏の知識・練習メニューの問いかけと、
 * 入力欄の書き出しの提案（SuggestionsQuery。AI なし）も用意する
 */
class CaseBoardQuery
{
    /** 使う係（ワトソン・いまの一手・見せ合いの表・教材・提案）を受け取る */
    public function __construct(
        private WatsonNotes $watson,
        private NextStep $nextStep,
        private RevealBoard $revealBoard,
        private GuideLibrary $library,
        private SuggestionsQuery $suggestions,
    ) {}

    /**
     * 画面に渡す値を返す。$turn は相棒モードの番（'partner'・'self'）、$open は開いておく入力欄（'predict-12'・'close'）、
     * $alert は保存した直後の警告の規則（W-5）
     */
    public function handle(CaseFile $case, User $user, ?string $turn, ?string $open, ?string $alert): array
    {
        // 閉じた事件の画面が「この事件からもう棚にしまったこと」を見せられるよう、屋根裏の知識も一緒に読む（あれば「つまり？」を聞き直さない）
        $case->load([
            'clues', 'predictions', 'dismissedHints', 'hypotheses.fits', 'atticNotes',
            'hypotheses.predictions' => fn ($q) => $q->oldest('id'),
        ]);
        $today = today()->toDateString();
        $turn = $case->hidesHypotheses() ? ($turn === 'self' ? 'self' : 'partner') : null;
        $visible = $turn === null ? $case->hypotheses : $case->hypotheses->filter(fn ($h) => $h->author->value === $turn)->values();
        $step = $this->nextStep->for($case, $turn, $today);
        // いまの一手がもう「予想を立てる」と言っているなら、同じことを聞く W-6（いつ確かめる？）は出さない
        $skip = $step['key'] === 'predict' ? ['no_check_date'] : [];
        $kid = $user->prefersKidWords(); // 子ども向けの言葉を出すか（ふだんの場面が親子の人）
        $suggest = $this->suggestions->for($case, $user, $visible, $kid, $today);
        $attic = $user->atticNotes()->latest('id')->limit(config('suiri.cases.attic_options'))->pluck('rule', 'id')->all();

        return [
            'case' => $case,
            'hypotheses' => $visible,
            'hiddenCount' => $case->hypotheses->count() - $visible->count(),
            'turn' => $turn,
            'partnerName' => $user->partnerLabel(),
            'selfName' => $user->name, // 相棒モードの渡し合いで「父さんの番だよ」のように自分を呼ぶ名前
            'kid' => $kid,
            // 答え合わせを待つカードが「カレンダーに入れたよ」か「やることに入れたよ」かを選ぶため、カレンダーとつないでいるか
            'calendarConnected' => (bool) $user->calendarConnection?->isActive(),
            'step' => $step,
            'watson' => $this->watson->forCase($case, $alert, $skip),
            'board' => $case->partner_mode && $turn === null ? $this->revealBoard->build($case) : null,
            // 予想で選べる屋根裏の知識。提案した知識が新しい順の件数から外れていても選べるよう、後ろに足す
            'atticOptions' => $attic + collect($suggest['attic'])->pluck('rule', 'id')->all(),
            'suggest' => $suggest,
            'prompts' => $case->practice_key ? ($this->library->practice($case->practice_key)['prompts'] ?? null) : null,
            'open' => $open,
            'today' => $today,
            'checkDefault' => today()->addDays(config('suiri.cases.check_after_days'))->toDateString(),
        ];
    }
}
