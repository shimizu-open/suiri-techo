<?php

namespace App\Actions\Guide;

use App\Models\SceneAttempt;
use App\Models\User;

/** 名推理カードへの自分の推理を保存する用事。1人1カードに1行なので、前に書いたものがあれば上書きする */
class SaveSceneAttempt
{
    /** 推理を保存して返す。何も書いていなければ保存せずに null を返す（前に書いた推理はそのまま残る） */
    public function handle(User $user, string $sceneKey, ?string $answer): ?SceneAttempt
    {
        // 空のまま送られたとき（書かずに裏返したとき）は、何も書き換えない
        if (blank($answer)) {
            return null;
        }

        return $user->sceneAttempts()->updateOrCreate(['scene_key' => $sceneKey], ['answer' => $answer]);
    }
}
