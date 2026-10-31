<?php

namespace App\Models;

use App\Enums\ClueKind;
use App\Models\Concerns\CopiesOwnerFromCase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 手がかり（事件について確かめた事実）。種類は 見た・聞いた・なかった。
 * user_id は持ち主で絞るための写し（事件から作れば自動で入る）
 */
#[Fillable(['case_file_id', 'user_id', 'kind', 'body'])]
class Clue extends Model
{
    use CopiesOwnerFromCase, HasFactory;

    /** 列の型を決める */
    protected function casts(): array
    {
        return [
            'kind' => ClueKind::class,
        ];
    }

    /** この手がかりの事件 */
    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** ○×を付けた見立て（pivot->fit が Fit 列挙） */
    public function hypotheses(): BelongsToMany
    {
        return $this->belongsToMany(Hypothesis::class, 'clue_hypothesis')
            ->using(ClueHypothesis::class)
            ->withPivot('id', 'fit')
            ->withTimestamps();
    }
}
