<?php

namespace App\Http\Requests\Cases;

use App\Enums\CaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 事件を閉じる送信を確かめる（C-9）：解決か迷宮入り（必須）・結論・「つまり？」「たとえば？」（任意）。
 * 「たとえば？」だけでは棚に置けないので、そのときは「つまり？」も求める
 */
class CloseCaseRequest extends FormRequest
{
    /** 捜査中の事件だけ閉じられる（持ち主かはルートで確かめ済み） */
    public function authorize(): bool
    {
        return $this->route('case')->isOpen();
    }

    /** 送る前に、文の前後の空白を取る */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'conclusion' => trim((string) $this->input('conclusion')),
            'rule' => trim((string) $this->input('rule')),
            'examples' => trim((string) $this->input('examples')),
        ]);
    }

    /** 項目ごとの決まり */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([CaseStatus::Solved->value, CaseStatus::Cold->value])],
            'conclusion' => ['nullable', 'string', 'max:'.config('suiri.cases.conclusion_max')],
            'rule' => ['nullable', 'required_with:examples', 'string', 'max:'.config('suiri.limits.attic_rule')],
            'examples' => ['nullable', 'string', 'max:'.config('suiri.limits.attic_examples')],
        ];
    }

    /** エラーに出す項目の名前 */
    public function attributes(): array
    {
        return ['status' => '閉じ方', 'conclusion' => '結論', 'rule' => 'つまり？', 'examples' => 'たとえば？'];
    }
}
