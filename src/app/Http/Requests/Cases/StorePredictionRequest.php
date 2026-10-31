<?php

namespace App\Http\Requests\Cases;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 予想を立てる送信を確かめる（C-6）：「もしそうなら〜が見えるはず」の文・確かさ（50〜95％の選択肢）・確かめる日（今日以降）・
 * 確かめる時刻（任意）・使う屋根裏の知識（任意。自分の棚のものだけ）
 */
class StorePredictionRequest extends FormRequest
{
    /** 相棒モードなら見せ合ったあとの事件だけ予想を立てられる（持ち主か・捜査中かはルートの ->can('write', 'hypothesis') で確かめ済み） */
    public function authorize(): bool
    {
        return ! $this->route('hypothesis')->caseFile->hidesHypotheses();
    }

    /** 送る前に、文の前後の空白を取る */
    protected function prepareForValidation(): void
    {
        $this->merge(['statement' => trim((string) $this->input('statement'))]);
    }

    /**
     * 項目ごとの決まり（確かさの選択肢は config/suiri.php の confidence.choices）。
     * 文に穴埋めの印（＿＿。予想の穴埋めの提案 S-3 が入れる）が残っていたら通さない（保存後は直せないため、書き換えてから保存してもらう）
     */
    public function rules(): array
    {
        return [
            'statement' => ['required', 'string', 'max:'.config('suiri.limits.prediction_statement'), 'not_regex:/'.preg_quote(config('suiri.suggest.blank'), '/').'/u'],
            'confidence' => ['required', 'integer', Rule::in(array_keys(config('suiri.confidence.choices')))],
            'check_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_at' => ['nullable', 'date_format:H:i'],
            'attic_note_id' => ['nullable', 'integer', Rule::exists('attic_notes', 'id')->where('user_id', $this->user()->id)],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return [
            'statement' => '予想の文',
            'confidence' => '確かさ',
            'check_on' => '確かめる日',
            'check_at' => '確かめる時刻',
            'attic_note_id' => '屋根裏の知識',
        ];
    }
}
