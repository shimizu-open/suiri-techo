<?php

namespace App\Actions\Settings;

use App\Models\User;

/** 設定（表示名・相棒の名前・ふだんの場面・記録のきっかけ）を保存する用事 */
class UpdateSettings
{
    /** 検証済みの値で設定を保存する。空にした相棒の名前・きっかけは null にする */
    public function handle(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'],
            'partner_name' => filled($data['partner_name'] ?? null) ? $data['partner_name'] : null,
            'default_scene' => $data['default_scene'],
            'cue' => filled($data['cue'] ?? null) ? $data['cue'] : null,
        ])->save();

        return $user;
    }
}
