<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 週の問いの送信を確かめる：今週いちばん考えが変わった事件（任意・自分の事件だけ）・つまり？（任意）・たとえば？（任意）
 */
class UpdateWeeklyReflectionRequest extends FormRequest
{
    /** ログインして同意した人なら送ってよい（ルートで確かめ済み。自分の週しか書けない） */
    public function authorize(): bool
    {
        return true;
    }

    /** 送る前に、前後の空白を取り、「選ばない」の事件を null にそろえる */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'changed_case_file_id' => filled($this->input('changed_case_file_id')) ? $this->input('changed_case_file_id') : null,
            'abstraction' => trim((string) $this->input('abstraction')),
            'concrete' => trim((string) $this->input('concrete')),
        ]);
    }

    /** 項目ごとの決まり。事件は本人のものだけ選べる（他人の事件の id を送られても通さない）。長さの上限は config/suiri.php の limits */
    public function rules(): array
    {
        $max = 'max:'.config('suiri.limits.reflection_text');

        return [
            'changed_case_file_id' => ['nullable', 'integer', Rule::exists('case_files', 'id')->where('user_id', $this->user()->id)],
            'abstraction' => ['nullable', 'string', $max],
            'concrete' => ['nullable', 'string', $max],
        ];
    }
}
