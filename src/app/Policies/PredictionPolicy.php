<?php

namespace App\Policies;

use App\Policies\Concerns\OwnerOnly;

/**
 * 予想を、持ち主だけが見る・直す・消すことができるようにする（view・update・delete）。答え合わせ・取り下げも update で確かめる。
 * App\Models\Prediction に自動で結びつく（名前の決まりで Laravel が見つける）。使い方：ルートの ->can('update', '…')
 */
class PredictionPolicy
{
    use OwnerOnly;
}
