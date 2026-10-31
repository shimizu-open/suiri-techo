<?php

namespace App\Models;

use App\Enums\Fit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * ○×（手がかりが見立てに合うか）の1行。見立てと手がかりの組ごとに1行（同じ組は2行にならない）。
 * Hypothesis::clues()・Clue::hypotheses() の pivot としても、Hypothesis::fits() の行としても使う
 */
class ClueHypothesis extends Pivot
{
    use HasFactory;

    /** 表の名前 */
    protected $table = 'clue_hypothesis';

    /** この表は自分の id（連番）を持つ */
    public $incrementing = true;

    /** 書き込んでよい列 */
    protected $fillable = ['hypothesis_id', 'clue_id', 'fit'];

    /** 列の型を決める */
    protected function casts(): array
    {
        return [
            'fit' => Fit::class,
        ];
    }

    /** ○×を付けた見立て */
    public function hypothesis(): BelongsTo
    {
        return $this->belongsTo(Hypothesis::class);
    }

    /** ○×を付けた手がかり */
    public function clue(): BelongsTo
    {
        return $this->belongsTo(Clue::class);
    }
}
