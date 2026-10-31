<?php

namespace App\Policies;

use App\Policies\Concerns\OwnerOnly;
use App\Policies\Concerns\WritesOpenCase;

/**
 * 見立てを、持ち主だけが見る・直す・消すことができるようにする（view・update・delete）。
 * 直す・消す・○×を付ける・予想を立てるときは write（持ち主で、事件が捜査中のときだけ。閉じた事件は「開き直してください」の 403）。
 * App\Models\Hypothesis に自動で結びつく（名前の決まりで Laravel が見つける）。使い方：ルートの ->can('update', '…')
 */
class HypothesisPolicy
{
    use OwnerOnly, WritesOpenCase;
}
