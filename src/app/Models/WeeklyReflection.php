<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 週のふりかえり（週の問いの答え）。1人1週に1行。
 * week_start は週の始まり（月曜日）の日付だけの列なので、キャストせず 'Y-m-d' の文字列のまま比べる
 */
#[Fillable(['week_start', 'changed_case_file_id', 'abstraction', 'concrete'])]
class WeeklyReflection extends Model
{
    use HasFactory;

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 今週いちばん考えが変わった事件（任意） */
    public function changedCase(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'changed_case_file_id');
    }
}
