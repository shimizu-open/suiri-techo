<?php

namespace App\Http\Requests\Cases;

use App\Enums\ClueKind;
use App\Enums\OriginKind;
use App\Enums\Scene;
use App\Support\Guide\GuideLibrary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 事件を作る画面の送信を確かめる（C-1・C-10・C-11）：なぞ（必須）・場面・相棒モード・きっかけ・練習メニュー、
 * 最初の手がかり（種類と本文）・最初の見立て（どちらも任意）。一番軽い記録は、なぞと手がかり1つと見立て1つ
 */
class StoreCaseRequest extends FormRequest
{
    /** ログインして同意した人なら送ってよい（自分の事件として作るだけ） */
    public function authorize(): bool
    {
        return true;
    }

    /** 送る前に前後の空白を取り、相棒の名前がない人の相棒モードと、練習でないきっかけの練習メニューを外す */
    protected function prepareForValidation(): void
    {
        $origin = $this->input('origin_kind');
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'clue_kind' => $this->input('clue_kind') ?: ClueKind::Seen->value,
            'clue_body' => trim((string) $this->input('clue_body')),
            'hypothesis_body' => trim((string) $this->input('hypothesis_body')),
            'origin_label' => mb_substr(trim((string) $this->input('origin_label')), 0, config('suiri.limits.origin_label')),
            'partner_mode' => $this->boolean('partner_mode') && $this->user()->hasPartner(),
            'practice_key' => $origin === OriginKind::Practice->value ? $this->input('practice_key') : null,
        ]);
    }

    /** 項目ごとの決まり（長さの上限は config/suiri.php の limits、練習メニューは手引きの教材にあるキーだけ） */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.config('suiri.limits.case_title')],
            'scene' => ['required', Rule::enum(Scene::class)],
            'partner_mode' => ['boolean'],
            'origin_kind' => ['nullable', Rule::enum(OriginKind::class)],
            'origin_label' => ['nullable', 'string', 'max:'.config('suiri.limits.origin_label')],
            'practice_key' => ['nullable', Rule::in(array_column(app(GuideLibrary::class)->practices(), 'key'))],
            'clue_kind' => ['required', Rule::enum(ClueKind::class)],
            'clue_body' => ['nullable', 'string', 'max:'.config('suiri.limits.clue_body')],
            'hypothesis_body' => ['nullable', 'string', 'max:'.config('suiri.limits.hypothesis_body')],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return [
            'title' => 'なぞ',
            'clue_kind' => '手がかりの種類',
            'clue_body' => '最初の手がかり',
            'hypothesis_body' => '最初の見立て',
        ];
    }
}
