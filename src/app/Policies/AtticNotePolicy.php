<?php

namespace App\Policies;

use App\Policies\Concerns\OwnerOnly;

/**
 * 屋根裏の棚の知識を、持ち主だけが見る・直す・消すことができるようにする（view・update・delete）。
 * App\Models\AtticNote に自動で結びつく（名前の決まりで Laravel が見つける）。使い方：ルートの ->can('update', '…')
 */
class AtticNotePolicy
{
    use OwnerOnly;
}
