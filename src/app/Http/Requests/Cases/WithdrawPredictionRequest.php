<?php

namespace App\Http\Requests\Cases;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 予想の取り下げの送信を確かめる（C-6）。送る項目はない。
 * 答え合わせ待ちの予想だけを受け付ける（答え合わせ済み・取り下げ済みの予想は 403。ノーベリ帳と確かさの集計を食い違わせないため）
 */
class WithdrawPredictionRequest extends FormRequest
{
    /** 答え合わせ待ちの予想かを確かめる（持ち主かはルートで確かめ済み） */
    public function authorize(): bool
    {
        return $this->route('prediction')->isPending();
    }

    /** 項目ごとの決まり（送る項目はない） */
    public function rules(): array
    {
        return [];
    }
}
