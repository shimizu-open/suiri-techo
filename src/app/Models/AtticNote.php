<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 屋根裏の棚の1枚（つまり？＝一般則 と たとえば？＝具体例）。事件からでも、手でも足せる */
#[Fillable(['case_file_id', 'rule', 'examples'])]
class AtticNote extends Model
{
    use HasFactory;

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** どの事件からわかったか（任意） */
    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    /** この知識を使った予想 */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }
}
