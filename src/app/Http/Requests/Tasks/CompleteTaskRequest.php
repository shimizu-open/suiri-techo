<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

/**
 * タスクを済みにするときの送信を確かめる。かかった時間（分）は任意で一緒に送れる（済んだあとに足すときもここ）。
 * 予想から作った確かめるタスクは、ここでは済みにできない（答え合わせを済ませると自動で済みになる）
 */
class CompleteTaskRequest extends FormRequest
{
    /** 予想につながった確かめるタスクでなければ送ってよい（持ち主かどうかはルートで確かめ済み） */
    public function authorize(): bool
    {
        return ! $this->route('task')->answeredByPrediction();
    }

    /** かかった時間の決まり（分の上限は config/suiri.php） */
    public function rules(): array
    {
        return [
            'actual_minutes' => ['nullable', 'integer', 'min:1', 'max:'.config('suiri.tasks.max_minutes')],
        ];
    }
}
