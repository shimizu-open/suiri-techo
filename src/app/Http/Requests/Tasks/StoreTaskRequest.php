<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

/**
 * タスクを作るときの送信を確かめる：題名（必須）・期日・開始時刻・予想時間・メモ（どれも任意）。
 * 種類はいつも「ふつう」（確かめるタスクは予想から自動で作るので、ここでは選ばせない）。
 * 開始時刻は期日がある日の時刻なので、開始時刻を入れるなら期日も求める
 */
class StoreTaskRequest extends FormRequest
{
    /** ログインして同意した人なら送ってよい（ルートで確かめ済み。作るのは自分のタスクだけ） */
    public function authorize(): bool
    {
        return true;
    }

    /** 確かめる前に、題名とメモの前後の空白を取り、空の欄を null にそろえる */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'memo' => trim((string) $this->input('memo')) ?: null,
        ]);
    }

    /** 項目ごとの決まり（長さと分の上限は config/suiri.php） */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.config('suiri.limits.task_title')],
            'memo' => ['nullable', 'string', 'max:'.config('suiri.limits.task_memo')],
            'due_on' => ['nullable', 'date_format:Y-m-d', 'required_with:starts_at'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:'.config('suiri.tasks.max_minutes')],
        ];
    }
}
