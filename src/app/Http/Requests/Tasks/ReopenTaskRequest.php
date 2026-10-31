<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 済んだタスクを未完了に戻すときの送信を確かめる。送る項目はない。
 * 予想から作った確かめるタスクは、答え合わせで済みになったものなので、ここでは戻せない
 */
class ReopenTaskRequest extends FormRequest
{
    /** 予想につながった確かめるタスクでなければ送ってよい（持ち主かどうかはルートで確かめ済み） */
    public function authorize(): bool
    {
        return ! $this->route('task')->answeredByPrediction();
    }

    /** 送る項目はないので決まりもない */
    public function rules(): array
    {
        return [];
    }
}
