<?php

namespace App\Actions\Attic;

use App\Models\AtticNote;
use App\Models\User;

/**
 * 屋根裏の棚の知識（つまり？・たとえば？・どの事件から）を保存する用事。
 * 知識を渡さなければ新しく作り、渡せばその知識を直す。持ち主かどうかはルートの ->can() で確かめ済み
 */
class SaveAtticNote
{
    /** 検証済みの値で知識を保存して返す。空のたとえば？・事件は null にする */
    public function handle(User $user, array $data, ?AtticNote $note = null): AtticNote
    {
        $note ??= $user->atticNotes()->make();
        $note->fill([
            'rule' => $data['rule'],
            'examples' => filled($data['examples'] ?? null) ? $data['examples'] : null,
            'case_file_id' => $data['case_file_id'] ?? null,
        ])->save();

        return $note;
    }
}
