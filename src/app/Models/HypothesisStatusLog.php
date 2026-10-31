<?php

namespace App\Models;

use App\Enums\HypothesisStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 見立ての状態を変えた記録（「考えを変えた」記録）。ふりかえりで数える。
 * 書いたあとは直さないので updated_at は持たない。user_id が空なら見立ての持ち主を写す
 */
#[Fillable(['hypothesis_id', 'user_id', 'from_status', 'to_status'])]
class HypothesisStatusLog extends Model
{
    use HasFactory;

    /** updated_at の列はない */
    public const UPDATED_AT = null;

    /** 列の型を決める */
    protected function casts(): array
    {
        return [
            'from_status' => HypothesisStatus::class,
            'to_status' => HypothesisStatus::class,
        ];
    }

    /** 作る直前に、user_id が空なら見立ての持ち主を写す決まりを登録する */
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            // user_id が渡されていなければ、見立ての持ち主を写す
            if ($log->user_id === null) {
                $log->user_id = Hypothesis::whereKey($log->hypothesis_id)->value('user_id');
            }
        });
    }

    /** どの見立ての記録か */
    public function hypothesis(): BelongsTo
    {
        return $this->belongsTo(Hypothesis::class);
    }

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
