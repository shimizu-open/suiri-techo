<?php

namespace App\Policies\Concerns;

use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * 「捜査中の事件にだけ書き込める」を1か所にまとめた部品（能力の名前は write）。OwnerOnly と一緒に使う。
 * 閉じた（解決・迷宮入り）事件には、手がかり・見立て・○×・予想・見せ合うを足したり直したりできない。
 * 画面にボタンを出さないだけでは、古いタブや手で作った送信が通ってしまうので、受付（ルートの ->can('write', …)）でも断る。
 * 答え合わせと取り下げは、閉じた事件でもできるまま（ここは使わない。理由は routes/web/cases.php）
 */
trait WritesOpenCase
{
    /** 書き込んでよいか（持ち主で、事件が捜査中のときだけ）。閉じた事件なら「開き直してください」の文つきで 403 にする */
    public function write(User $user, Model $record): Response
    {
        // 持ち主でなければ断る（他人の記録は、閉じているかどうかを知らせずに 403）
        if (! $this->owns($user, $record)) {
            return Response::deny();
        }

        $case = $record instanceof CaseFile ? $record : $record->getAttribute('caseFile');

        // 事件が捜査中なら通し、閉じていれば開き直すよう伝えて断る
        return $case->isOpen() ? Response::allow() : Response::deny(config('suiri.cases.closed_message'));
    }
}
