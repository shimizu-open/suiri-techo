<?php

namespace App\Models;

use App\Enums\MissReason;
use App\Enums\PredictionResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * 予想（もしこの見立てが正しいなら〜が見えるはず）。演繹の段。
 * 後知恵を防ぐため、文（statement）と確かさ（confidence）は保存後に書き換えられない（書き換えようとすると例外）。
 * check_on は日付だけの列なので、キャストせず 'Y-m-d' の文字列のまま比べる。created_at が「書いた日時」
 */
#[Fillable([
    'case_file_id', 'hypothesis_id', 'user_id', 'attic_note_id', 'statement', 'confidence',
    'check_on', 'check_at', 'result', 'result_note', 'miss_reason', 'resolved_at', 'withdrawn_at',
])]
class Prediction extends Model
{
    use HasFactory;

    /** 保存後に書き換えてはいけない列 */
    public const FROZEN = ['statement', 'confidence'];

    /** 列の型を決める（日付だけの check_on はキャストしない） */
    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
            'result' => PredictionResult::class,
            'miss_reason' => MissReason::class,
            'resolved_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** 作る直前に事件と持ち主をそろえ、直す直前に「文と確かさは変えない」を確かめる決まりを登録する */
    protected static function booted(): void
    {
        static::creating(function (self $prediction) {
            // 事件が渡されていなければ、見立ての事件を写す（$hypothesis->predictions()->create() で作ったとき）
            if ($prediction->case_file_id === null) {
                $prediction->case_file_id = Hypothesis::whereKey($prediction->hypothesis_id)->value('case_file_id');
            }
            // 持ち主が渡されていなければ、事件の持ち主を写す
            if ($prediction->user_id === null) {
                $prediction->user_id = CaseFile::whereKey($prediction->case_file_id)->value('user_id');
            }
        });

        static::updating(function (self $prediction) {
            // 文か確かさを書き換えようとしていたら止める（考えが変わったら新しい予想を立てる決まり）
            if ($prediction->isDirty(self::FROZEN)) {
                throw new LogicException('予想の文と確かさは、保存したあとに書き換えられません。');
            }
        });
    }

    /** この予想の事件 */
    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    /** この予想を導いた見立て */
    public function hypothesis(): BelongsTo
    {
        return $this->belongsTo(Hypothesis::class);
    }

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 使った屋根裏の知識（任意） */
    public function atticNote(): BelongsTo
    {
        return $this->belongsTo(AtticNote::class);
    }

    /** この予想から作った確かめるタスク */
    public function task(): HasOne
    {
        return $this->hasOne(Task::class);
    }

    /** 答え合わせ待ち（まだ結果がなく、取り下げてもいない）かを返す */
    public function isPending(): bool
    {
        return $this->result === null && $this->withdrawn_at === null;
    }

    /** 答え合わせ済みかを返す */
    public function isResolved(): bool
    {
        return $this->result !== null;
    }

    /** 取り下げたかを返す */
    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }

    /** 答え合わせ待ちで、確かめる日がその日（'Y-m-d'）までに来ているかを返す */
    public function isDue(string $today): bool
    {
        return $this->isPending() && $this->check_on <= $today;
    }
}
