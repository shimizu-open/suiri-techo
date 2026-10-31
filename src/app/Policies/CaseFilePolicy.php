<?php

namespace App\Policies;

use App\Policies\Concerns\OwnerOnly;
use App\Policies\Concerns\WritesOpenCase;

/**
 * 事件を、持ち主だけが見る・直す・消すことができるようにする（view・update・delete）。閉じる・開き直すときも update で確かめる。
 * 手がかりや見立てを足す・見せ合うときは write（持ち主で、事件が捜査中のときだけ。閉じた事件は「開き直してください」の 403）。
 * App\Models\CaseFile に自動で結びつく（名前の決まりで Laravel が見つける）。使い方：ルートの ->can('update', '…')
 */
class CaseFilePolicy
{
    use OwnerOnly, WritesOpenCase;
}
