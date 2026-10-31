<?php

namespace App\Models;

use App\Models\Concerns\CopiesOwnerFromCase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 閉じたワトソンのひとこと。事件と規則（config/watson.php のキー）の組ごとに1行。updated_at は持たない */
#[Fillable(['case_file_id', 'user_id', 'rule'])]
class DismissedHint extends Model
{
    use CopiesOwnerFromCase, HasFactory;

    /** updated_at の列はない */
    public const UPDATED_AT = null;

    /** どの事件のひとことか */
    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
