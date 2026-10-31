<?php

namespace App\Actions\Auth;

use App\Models\User;

/** 利用規約・プライバシーポリシー・年齢の確認への同意を記録する用事（agreed_at に今の日時を入れる） */
class AcceptTerms
{
    /** 同意した日時を記録する。すでに同意済みなら最初の日時のまま変えない */
    public function handle(User $user): void
    {
        // まだ同意していなければ、今の日時を入れる
        if (! $user->hasAgreed()) {
            $user->forceFill(['agreed_at' => now()])->save();
        }
    }
}
