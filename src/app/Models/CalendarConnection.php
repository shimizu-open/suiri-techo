<?php

namespace App\Models;

use App\Enums\CalendarStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Google カレンダー連携（1人1行）。トークンは encrypted キャストで暗号化して保存し、JSON（データのダウンロードなど）には出さない
 */
#[Fillable([
    'access_token', 'refresh_token', 'expires_at', 'scopes', 'app_calendar_id',
    'sync_token', 'status', 'last_synced_at',
])]
#[Hidden(['access_token', 'refresh_token', 'sync_token'])]
class CalendarConnection extends Model
{
    use HasFactory;

    /** 新しく作るときの既定の値 */
    protected $attributes = [
        'status' => 'active',
    ];

    /** 列の型を決める（トークンは暗号化、スコープは配列） */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'scopes' => 'array',
            'status' => CalendarStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }

    /** 持ち主 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** つながっている（切れていない）かを返す */
    public function isActive(): bool
    {
        return $this->status === CalendarStatus::Active;
    }

    /** 許可が取り消された・切れたかを返す（「つなぎ直してください」と出す） */
    public function isBroken(): bool
    {
        return $this->status === CalendarStatus::Broken;
    }

    /** そのスコープ（許可の範囲）をもらっているかを返す */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }
}
