<?php

namespace App\Services\Watson;

use App\Enums\ClueKind;
use App\Enums\Scene;
use App\Models\CaseFile;
use Illuminate\Support\Str;

/**
 * ワトソンのひとこと（W-1〜W-6）を、決まった規則で選ぶ係。AI は使わない。
 * 事件の詳細では、当てはまる規則のうち優先度の一番高いもの（config/watson.php の rules の上から）を1つだけ返す。
 * 閉じた規則（dismissed_hints）は出さない。保存を止めることはしない（画面に問いかけを出すだけ）。
 * 事件は clues・hypotheses・predictions・dismissedHints を読み込んだ状態で渡す（ここではデータを取りに行かない）
 */
class WatsonNotes
{
    /** 文の中のしるしを探す係を受け取る */
    public function __construct(private TextSigns $signs) {}

    /**
     * 事件に出すひとことを1つ返す。なければ null。
     * $alert に規則のキー（例 'personal_info'）を渡すと、閉じていてもそれを先に返す（保存した直後の警告）。
     * $skip に渡した規則は確かめない（画面のほかの場所が同じことをもう言っているとき。例：いまの一手が「予想を立てる」なら W-6）。
     *
     * @param  array<int, string>  $skip
     * @return array{rule: string, id: string, title: string, body: string, quote: ?string, alert: bool}|null
     */
    public function forCase(CaseFile $case, ?string $alert = null, array $skip = []): ?array
    {
        // 保存した直後の警告があれば、閉じたかどうかに関係なく、それを出す
        if ($alert !== null && config("watson.rules.{$alert}") !== null) {
            return $this->note($alert, null, true);
        }
        $dismissed = [...$case->dismissedHints->pluck('rule')->all(), ...$skip];

        // 規則を優先度の高い順に1つずつ確かめ、閉じていなくて当てはまる最初の1つを返す
        foreach (array_keys(config('watson.rules', [])) as $rule) {
            // 閉じた規則と、飛ばすように頼まれた規則は飛ばす
            if (in_array($rule, $dismissed, true)) {
                continue;
            }
            $quote = $this->match($rule, $case);
            // 当てはまったら（引用がなくても空文字が返る）、その規則のひとことを返す
            if ($quote !== null) {
                return $this->note($rule, $quote !== '' ? $quote : null);
            }
        }

        return null;
    }

    /** 保存した文の中に、ほかの人がわかる情報らしきものがあれば W-5 の規則のキーを返す（保存した直後の警告に使う） */
    public function alertFor(?string ...$texts): ?string
    {
        // 文を1つずつ確かめ、1つでも当てはまれば W-5
        foreach ($texts as $text) {
            // 空でなく、ほかの人がわかる情報らしきものがあれば知らせる
            if (filled($text) && $this->signs->hasPersonalInfo($text)) {
                return 'personal_info';
            }
        }

        return null;
    }

    /**
     * 規則が事件に当てはまるかを確かめる。当てはまらなければ null、当てはまれば引用（どの手がかりか）か空文字を返す
     */
    public function match(string $rule, CaseFile $case): ?string
    {
        // 規則のキーで、確かめ方を選ぶ（知らないキーは当てはまらない）
        return match ($rule) {
            'personal_info' => $this->any($this->allTexts($case), fn ($t) => $this->signs->hasPersonalInfo($t)) ? '' : null,
            'seen_guess' => $this->seenClue($case, fn ($t) => $this->signs->hasGuess($t)),
            'seen_cause' => $this->seenClue($case, fn ($t) => $this->signs->hasCause($t)),
            'work_better' => $case->scene === Scene::Work
                && $this->any($this->reasoningTexts($case), fn ($t) => $this->signs->hasFeelsBetter($t)) ? '' : null,
            'single_hypothesis' => $case->isOpen() && $case->hypotheses->count() === 1 ? '' : null,
            'no_check_date' => $this->lacksCheckDate($case) ? '' : null,
            default => null,
        };
    }

    /** 「見た」の手がかりの中で、条件に当てはまる最初のものの引用（短くした本文）を返す。なければ null */
    private function seenClue(CaseFile $case, callable $test): ?string
    {
        $clue = $case->clues->first(fn ($c) => $c->kind === ClueKind::Seen && $test($c->body));

        return $clue === null ? null : Str::limit($clue->body, config('watson.quote_length', 40), '…');
    }

    /** 捜査中で、まだ消えていない見立てがあるのに、答え合わせ待ちの予想（確かめる日のある予想）が1つもないかを返す（W-6） */
    private function lacksCheckDate(CaseFile $case): bool
    {
        // 閉じた事件や、見立てを隠している相棒モードでは聞かない
        if (! $case->isOpen() || $case->hidesHypotheses()) {
            return false;
        }

        return $case->hypotheses->contains(fn ($h) => $h->status->isAlive())
            && ! $case->predictions->contains(fn ($p) => $p->isPending() && filled($p->check_on));
    }

    /** 事件の中の文をすべて集める（なぞ・結論・手がかり・見立て・予想とその一言） */
    private function allTexts(CaseFile $case): array
    {
        return [$case->title, $case->conclusion, ...$this->reasoningTexts($case)];
    }

    /** 手がかり・見立て・予想の文と、答え合わせの一言を集める */
    private function reasoningTexts(CaseFile $case): array
    {
        return [
            ...$case->clues->pluck('body')->all(),
            ...$case->hypotheses->pluck('body')->all(),
            ...$case->predictions->pluck('statement')->all(),
            ...$case->predictions->pluck('result_note')->all(),
        ];
    }

    /** 文の一覧のどれか1つでも条件に当てはまるかを返す（空の文は飛ばす） */
    private function any(array $texts, callable $test): bool
    {
        return collect($texts)->filter(fn ($t) => filled($t))->contains(fn ($t) => $test($t));
    }

    /** 規則のキーから、画面に出すひとことの形を組み立てる */
    private function note(string $rule, ?string $quote, bool $alert = false): array
    {
        $config = config("watson.rules.{$rule}");

        return [
            'rule' => $rule,
            'id' => $config['id'],
            'title' => $config['title'],
            'body' => $alert && isset($config['alert']) ? $config['alert'].' '.$config['body'] : $config['body'],
            'quote' => $quote,
            'alert' => $alert,
        ];
    }
}
