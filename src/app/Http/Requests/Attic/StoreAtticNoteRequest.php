<?php

namespace App\Http\Requests\Attic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 屋根裏の棚の知識の送信を確かめる：つまり？（必須）・たとえば？（任意）・どの事件から（任意・自分の事件だけ）。
 * 直すとき（UpdateAtticNoteRequest）も同じ決まりを使う
 */
class StoreAtticNoteRequest extends FormRequest
{
    /** ログインして同意した人なら送ってよい（直す・消すときの持ち主の確認はルートの ->can() でする） */
    public function authorize(): bool
    {
        return true;
    }

    /** 送る前に、前後の空白を取り、「選ばない」の事件を null にそろえる */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'rule' => trim((string) $this->input('rule')),
            'examples' => trim((string) $this->input('examples')),
            'case_file_id' => filled($this->input('case_file_id')) ? $this->input('case_file_id') : null,
        ]);
    }

    /** 項目ごとの決まり。事件は本人のものだけ選べる。長さの上限は config/suiri.php の limits */
    public function rules(): array
    {
        return [
            'rule' => ['required', 'string', 'max:'.config('suiri.limits.attic_rule')],
            'examples' => ['nullable', 'string', 'max:'.config('suiri.limits.attic_examples')],
            'case_file_id' => ['nullable', 'integer', Rule::exists('case_files', 'id')->where('user_id', $this->user()->id)],
        ];
    }

    /** エラーの文に出す項目の名前（どの事件から） */
    public function attributes(): array
    {
        return ['case_file_id' => 'どの事件から'];
    }
}
