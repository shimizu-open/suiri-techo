<?php

namespace App\Models\Concerns;

use App\Models\CaseFile;

/**
 * 事件の中の記録（手がかり・見立て・閉じたひとこと）を作るとき、user_id が空なら親の事件の持ち主を写す部品。
 * $case->clues()->create([...]) のように事件から作れば、user_id を渡し忘れても持ち主がそろう
 */
trait CopiesOwnerFromCase
{
    /** モデルが起動するとき、「作る直前に持ち主を写す」決まりを登録する */
    protected static function bootCopiesOwnerFromCase(): void
    {
        static::creating(function ($model) {
            // user_id が渡されておらず、親の事件がわかるときは、事件の持ち主を写す
            if ($model->user_id === null && $model->case_file_id !== null) {
                $model->user_id = CaseFile::whereKey($model->case_file_id)->value('user_id');
            }
        });
    }
}
