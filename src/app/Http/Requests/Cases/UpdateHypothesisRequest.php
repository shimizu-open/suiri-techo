<?php

namespace App\Http\Requests\Cases;

use App\Enums\HypothesisStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 見立てを直す送信を確かめる：本文を直すか、状態（検討中・有力・消えた・当たり）を変えるか（どちらか1つ以上） */
class UpdateHypothesisRequest extends FormRequest
{
    /** 持ち主かはルートの ->can() で確かめ済みなので、ここでは通す */
    public function authorize(): bool
    {
        return true;
    }

    /** 本文が送られたときだけ、前後の空白を取る */
    protected function prepareForValidation(): void
    {
        // 本文の欄があるときだけ空白を取る（状態だけを変える送信には本文がない）
        if ($this->has('body')) {
            $this->merge(['body' => trim((string) $this->input('body'))]);
        }
    }

    /** 項目ごとの決まり */
    public function rules(): array
    {
        return [
            'body' => ['required_without:status', 'string', 'max:'.config('suiri.limits.hypothesis_body')],
            'status' => ['required_without:body', Rule::enum(HypothesisStatus::class)],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return ['body' => '見立て', 'status' => '見立ての状態'];
    }
}
