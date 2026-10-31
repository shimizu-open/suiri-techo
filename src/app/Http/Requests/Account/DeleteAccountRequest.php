<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 退会の送信を確かめる：うっかり押しても消えないよう、確認の言葉（config('suiri.review.delete_confirmation')）を入力してもらう */
class DeleteAccountRequest extends FormRequest
{
    /** ログインして同意した人なら送ってよい（自分のアカウントしか消せない） */
    public function authorize(): bool
    {
        return true;
    }

    /** 送る前に、前後の空白を取る */
    protected function prepareForValidation(): void
    {
        $this->merge(['confirmation' => trim((string) $this->input('confirmation'))]);
    }

    /** 確認の言葉と同じ文字が入っていること */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', Rule::in([config('suiri.review.delete_confirmation')])],
        ];
    }

    /** 確認の言葉がちがうときの文（何を入れればよいかを示す） */
    public function messages(): array
    {
        $word = config('suiri.review.delete_confirmation');

        return [
            'confirmation.required' => "確認のため「{$word}」と入力してください。",
            'confirmation.in' => "確認のため「{$word}」と入力してください。",
        ];
    }

    /** エラーの文に出す項目の名前 */
    public function attributes(): array
    {
        return ['confirmation' => '確認の言葉'];
    }
}
