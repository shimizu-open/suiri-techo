<?php

namespace App\Models;

use App\Enums\TaskKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * タスク（やること）。種類は「ふつう」と「確かめる」（予想から自動で作る）。
 * 期日 due_on は日付だけの列なので、キャストせず 'Y-m-d' の文字列のまま比べる（sqlite と MySQL の違いを避ける）。
 * duration_minutes は予想時間（カレンダーの予定の長さにもなる）、actual_minutes は完了のときに任意で入れる、かかった時間
 */
#[Fillable([
    'title', 'memo', 'due_on', 'starts_at', 'duration_minutes', 'actual_minutes', 'kind',
    'prediction_id', 'done_at', 'calendar_event_id', 'calendar_synced_at',
])]
class Task extends Model
{
    use HasFactory;

    /** 新しく作るときの既定の値 */
    protected $attributes = [
        'kind' => 'normal',
    ];

    /** 列の型を決める（日付だけの due_on はキャストしない） */
    protected function casts(): array
    {
        return [
            'kind' => TaskKind::class,
            'duration_minutes' => 'integer',
            'actual_minutes' => 'integer',
            'done_at' => 'datetime',
            'calendar_synced_at' => 'datetime',
        ];
    }

    /** タスクの持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 確かめるタスクのもとの予想（ふつうのタスクは null） */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class);
    }

    /** 済んだかを返す */
    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    /** 確かめるタスク（予想から作ったもの）かを返す */
    public function isVerify(): bool
    {
        return $this->kind === TaskKind::Verify;
    }

    /**
     * 予想の答え合わせで済み・未完了が決まるタスクかを返す（確かめるタスクで、もとの予想が残っているもの）。
     * こういうタスクは、丸を押して済みにしたり戻したりできない（受付も画面もこの1か所で判断する）
     */
    public function answeredByPrediction(): bool
    {
        return $this->isVerify() && $this->prediction_id !== null;
    }

    /**
     * 答え合わせ待ちの予想を確かめるタスクかを返す。こういうタスクは、期日・時刻を直すと予想の確かめる日・時刻も一緒に変わり、
     * 期日を空（いつか）にできず、消すときは予想の取り下げを使う（受付も画面もこの1か所で判断する）
     */
    public function checksPendingPrediction(): bool
    {
        return $this->answeredByPrediction() && $this->prediction?->isPending() === true;
    }

    /** 開始時刻があるか（終日でないか）を返す */
    public function hasTime(): bool
    {
        return filled($this->starts_at);
    }

    /** まだ済んでいないタスクだけに絞る */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('done_at');
    }

    /** 済んだタスクだけに絞る */
    #[Scope]
    protected function done(Builder $query): void
    {
        $query->whereNotNull('done_at');
    }
}
