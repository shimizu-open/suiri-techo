<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 名推理カードへの自分の推理。1人1カードに1行（書き直すと上書き）。scene_key は database/data/guide/scenes.json のキー */
#[Fillable(['scene_key', 'answer'])]
class SceneAttempt extends Model
{
    use HasFactory;

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
