<?php

namespace App\Http\Requests\Cases;

use App\Enums\ClueKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 手がかりを足す・直す送信を確かめる（C-2）：種類（見た・聞いた・なかった）と本文。直すとき（UpdateClueRequest）も同じ決まり */
class StoreClueRequest extends FormRequest
{
    /** 持ち主かはルートの ->can() で確かめ済みなので、ここでは通す */
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
            'kind' => ['required', Rule::enum(ClueKind::class)],
            'body' => ['required', 'string', 'max:'.config('suiri.limits.clue_body')],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return ['kind' => '手がかりの種類', 'body' => '手がかり'];
    }
}
