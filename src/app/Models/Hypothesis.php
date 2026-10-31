<?php

namespace App\Models;

use App\Enums\HypothesisAuthor;
use App\Enums\HypothesisStatus;
use App\Models\Concerns\CopiesOwnerFromCase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 見立て（説明の案）。2つ以上を勧める。書いた人（自分・相棒）と状態（検討中・有力・消えた・当たり）を持つ。
 * 状態を変えたら hypothesis_status_logs に残す（それは事件の担当の Action がする）
 */
#[Fillable(['case_file_id', 'user_id', 'author', 'body', 'status'])]
class Hypothesis extends Model
{
    use CopiesOwnerFromCase, HasFactory;

    /** 表の名前（hypothesis の複数形） */
    protected $table = 'hypotheses';

    /** 新しく作るときの既定の値 */
    protected $attributes = [
        'author' => 'self',
        'status' => 'considering',
    ];

    /** 列の型を決める */
    protected function casts(): array
    {
        return [
            'author' => HypothesisAuthor::class,
            'status' => HypothesisStatus::class,
        ];
    }

    /** この見立ての事件 */
    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** ○×を付けた手がかり（pivot->fit が Fit 列挙） */
    public function clues(): BelongsToMany
    {
        return $this->belongsToMany(Clue::class, 'clue_hypothesis')
            ->using(ClueHypothesis::class)
            ->withPivot('id', 'fit')
            ->withTimestamps();
    }

    /** ○×の行そのもの（手がかりごとに1行） */
    public function fits(): HasMany
    {
        return $this->hasMany(ClueHypothesis::class);
    }

    /** 状態を変えた記録（古い順） */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(HypothesisStatusLog::class)->oldest('id');
    }

    /** この見立てから導いた予想 */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /** 相棒が書いた見立てかを返す */
    public function isByPartner(): bool
    {
        return $this->author === HypothesisAuthor::Partner;
    }

    /** 消えた見立てかを返す */
    public function isEliminated(): bool
    {
        return $this->status === HypothesisStatus::Eliminated;
    }
}
