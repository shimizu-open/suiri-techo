<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * タスクを消すときの送信を確かめる。送る項目はない。
 * 答え合わせ待ちの予想を確かめるタスクは消せない（消すと答え合わせ待ちの予想だけが残るため）。確かめるのをやめるなら予想を取り下げる。
 * 持ち主かどうかはルートの ->can('delete', 'task') で確かめ済み
 */
class DeleteTaskRequest extends FormRequest
{
    /** 答え合わせ待ちの予想を確かめるタスクでなければ、消してよい */
    public function authorize(): bool
    {
        return ! $this->route('task')->checksPendingPrediction();
    }

    /** 消せないときは 403 にし、予想の取り下げを使うよう案内する */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('このタスクは予想を確かめるためのものなので、消せません。確かめるのをやめるときは、予想を取り下げてください。');
    }

    /** 項目ごとの決まり（送る項目はない） */
    public function rules(): array
    {
        return [];
    }
}
