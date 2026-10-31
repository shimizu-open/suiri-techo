<?php

namespace App\Policies;

use App\Policies\Concerns\OwnerOnly;
use App\Policies\Concerns\WritesOpenCase;

/**
 * 手がかりを、持ち主だけが見る・直す・消すことができるようにする（view・update・delete）。
 * 直す・消す・○×を付けるときは write（持ち主で、事件が捜査中のときだけ。閉じた事件は「開き直してください」の 403）。
 * App\Models\Clue に自動で結びつく（名前の決まりで Laravel が見つける）。使い方：ルートの ->can('update', '…')
 */
class CluePolicy
{
    use OwnerOnly, WritesOpenCase;
}
