<?php

namespace App\Models;

use App\Enums\CaseStatus;
use App\Enums\OriginKind;
use App\Enums\Scene;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 事件（気になる出来事と、その「なぞ」）。記録の単位。
 * case は PHP の予約語なので CaseFile と呼ぶ。ルートの {case} はこのモデルに結びつく（AppServiceProvider の Route::model）
 */
#[Fillable([
    'title', 'scene', 'status', 'origin_kind', 'origin_label', 'practice_key',
    'partner_mode', 'revealed_at', 'conclusion', 'closed_at',
])]
class CaseFile extends Model
{
    use HasFactory;

    /** 新しく作るときの既定の値 */
    protected $attributes = [
        'status' => 'open',
        'partner_mode' => false,
    ];

    /** 列の型を決める */
    protected function casts(): array
    {
        return [
            'scene' => Scene::class,
            'status' => CaseStatus::class,
            'origin_kind' => OriginKind::class,
            'partner_mode' => 'boolean',
            'revealed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** 事件の持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 手がかり（足した順） */
    public function clues(): HasMany
    {
        return $this->hasMany(Clue::class)->oldest('id');
    }

    /** 見立て（足した順） */
    public function hypotheses(): HasMany
    {
        return $this->hasMany(Hypothesis::class)->oldest('id');
    }

    /** 予想（すべての見立ての分） */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /** この事件からわかった屋根裏の知識 */
    public function atticNotes(): HasMany
    {
        return $this->hasMany(AtticNote::class);
    }

    /** この事件で閉じたワトソンのひとこと */
    public function dismissedHints(): HasMany
    {
        return $this->hasMany(DismissedHint::class);
    }

    /** 捜査中かを返す */
    public function isOpen(): bool
    {
        return $this->status === CaseStatus::Open;
    }

    /** 閉じた（解決・迷宮入り）かを返す */
    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /** 相棒モードで、まだ見せ合っていない（互いの見立てを隠す）かを返す */
    public function hidesHypotheses(): bool
    {
        return $this->partner_mode && $this->revealed_at === null;
    }
}
