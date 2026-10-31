<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * 「持ち主だけが見る・直す・消すことができる」を1か所にまとめた部品。
 * 記録の user_id とログイン中の人の id が同じときだけ許す。運営者でも他人の記録は見られない
 */
trait OwnerOnly
{
    /** 見てよいか（持ち主だけ） */
    public function view(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    /** 直してよいか（持ち主だけ） */
    public function update(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    /** 消してよいか（持ち主だけ） */
    public function delete(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    /** 記録の持ち主がこの人かを返す（型をそろえて比べる） */
    protected function owns(User $user, Model $record): bool
    {
        return (int) $record->getAttribute('user_id') === (int) $user->getKey();
    }
}
