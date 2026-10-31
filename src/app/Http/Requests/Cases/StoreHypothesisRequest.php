<?php

namespace App\Http\Requests\Cases;

use App\Enums\HypothesisAuthor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 見立てを足す送信を確かめる（C-3・C-11）：本文と、相棒モードなら書いた人（自分・相棒） */
class StoreHypothesisRequest extends FormRequest
{
    /** 持ち主か・事件が捜査中か（閉じた事件には足せない）はルートの ->can('write', 'case') で確かめ済みなので、ここでは通す */
    public function authorize(): bool
    {
        return true;
    }

    /** 送る前に、本文の前後の空白を取る */
    protected function prepareForValidation(): void
    {
        $this->merge(['body' => trim((string) $this->input('body'))]);
    }

    /** 項目ごとの決まり */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.config('suiri.limits.hypothesis_body')],
            'author' => ['nullable', Rule::enum(HypothesisAuthor::class)],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return ['body' => '見立て', 'author' => '書いた人'];
    }
}
