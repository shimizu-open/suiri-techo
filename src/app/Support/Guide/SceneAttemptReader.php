<?php

namespace App\Support\Guide;

use App\Models\SceneAttempt;
use App\Models\User;

/**
 * 名推理カードへの「自分の推理」（scene_attempts）を読み出す係。
 * 必ずその人の行だけから探す（他人の推理は見せない）
 */
class SceneAttemptReader
{
    /** その人が推理を書いたカードのキーの一覧を返す（一覧の画面で「書いた」の印を付けるため） */
    public function keys(User $user): array
    {
        return $user->sceneAttempts()->pluck('scene_key')->all();
    }

    /** その人がそのカードに書いた推理を返す。まだ書いていなければ null */
    public function find(User $user, string $sceneKey): ?SceneAttempt
    {
        return $user->sceneAttempts()->where('scene_key', $sceneKey)->first();
    }
}
