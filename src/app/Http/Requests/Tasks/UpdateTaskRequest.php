<?php

namespace App\Http\Requests\Tasks;

/**
 * タスクを直すときの送信を確かめる。作るときの決まりに、かかった時間（任意）を足したもの。
 * 予想から作った確かめるタスクは、題名（予想の文の写し）を受け付けない。答え合わせ待ちの予想のものは、期日を必須にする
 * （期日が予想の確かめる日になるので「いつか」にはできない）。持ち主かどうかはルートの ->can('update', 'task') で確かめ済み
 */
class UpdateTaskRequest extends StoreTaskRequest
{
    /** 作るときの決まり ＋ かかった時間（見積もりの答え合わせ）。確かめるタスクなら題名と期日の決まりを変える */
    public function rules(): array
    {
        $rules = parent::rules() + [
            'actual_minutes' => ['nullable', 'integer', 'min:1', 'max:'.config('suiri.tasks.max_minutes')],
        ];
        $task = $this->route('task');

        // 予想から作った確かめるタスクなら、題名の決まりを外す（送られても受け取らず、題名は予想の文の写しのまま）
        if ($task->answeredByPrediction()) {
            unset($rules['title']);
        }
        // 答え合わせ待ちの予想を確かめるタスクなら、期日を必須にする（予想の確かめる日を空にしない）
        if ($task->checksPendingPrediction()) {
            $rules['due_on'] = ['required', 'date_format:Y-m-d'];
        }

        return $rules;
    }
}
