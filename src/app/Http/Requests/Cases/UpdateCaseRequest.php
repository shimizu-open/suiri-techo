<?php

namespace App\Http\Requests\Cases;

use App\Enums\Scene;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 事件のなぞと場面を直す送信を確かめる（持ち主かどうかはルートの ->can('update', 'case') で確かめ済み） */
class UpdateCaseRequest extends FormRequest
{
    /** 持ち主かはルートで確かめ済みなので、ここでは通す */
    public function authorize(): bool
    {
        return true;
    }

    /** 送る前に、なぞの前後の空白を取る */
    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim((string) $this->input('title'))]);
    }

    /** 項目ごとの決まり */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.config('suiri.limits.case_title')],
            'scene' => ['required', Rule::enum(Scene::class)],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return ['title' => 'なぞ'];
    }
}
