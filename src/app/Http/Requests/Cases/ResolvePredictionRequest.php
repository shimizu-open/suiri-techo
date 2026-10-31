<?php

namespace App\Http\Requests\Cases;

use App\Enums\MissReason;
use App\Enums\PredictionResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 答え合わせの送信を確かめる（C-7・C-8）：4つの結果（必須）・一言（任意）・外れなら「何を見落とした？」（必須）。
 * 答え合わせ待ちの予想だけを受け付ける（済んだ・取り下げた予想は断る）
 */
class ResolvePredictionRequest extends FormRequest
{
    /** 答え合わせ待ちの予想かを確かめる（持ち主かはルートで確かめ済み） */
    public function authorize(): bool
    {
        return $this->route('prediction')->isPending();
    }

    /** 送る前に、一言の前後の空白を取る */
    protected function prepareForValidation(): void
    {
        $this->merge(['result_note' => trim((string) $this->input('result_note'))]);
    }

    /** 項目ごとの決まり */
    public function rules(): array
    {
        return [
            'result' => ['required', Rule::enum(PredictionResult::class)],
            'result_note' => ['nullable', 'string', 'max:'.config('suiri.limits.result_note')],
            'miss_reason' => ['nullable', 'required_if:result,'.PredictionResult::Miss->value, Rule::enum(MissReason::class)],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return ['result' => '答え合わせの結果', 'result_note' => '一言', 'miss_reason' => '何を見落とした？'];
    }

    /** 外れのときに原因がなかった場合のエラーの文 */
    public function messages(): array
    {
        return ['miss_reason.required_if' => '外れたときは、何を見落としたかを選んでください。'];
    }
}
