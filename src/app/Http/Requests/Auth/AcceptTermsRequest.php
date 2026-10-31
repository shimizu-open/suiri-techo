<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** 同意の画面の送信を確かめる：利用規約とプライバシーポリシーへの同意と、年齢の確認の2つのチェックが両方入っていること */
class AcceptTermsRequest extends FormRequest
{
    /** ログイン中の人なら送ってよい（ルートの auth で確かめ済み） */
    public function authorize(): bool
    {
        return true;
    }

    /** 2つのチェックが両方入っていることを求める */
    public function rules(): array
    {
        return [
            'accept_terms' => ['accepted'],
            'confirm_age' => ['accepted'],
        ];
    }
}
