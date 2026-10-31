<?php

namespace App\Models;

use App\Enums\Scene;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * 利用者。すべての記録はこの人を軸にする（他人を表す表は作らない）。
 * ログインは Google だけなので、パスワードは持たない。google_id（Google の sub）と agreed_at は forceFill で入れる。
 * 本人が許可した AI（ChatGPT・Claude）は、通行証（Laravel Passport の HasApiTokens）でこの人として窓口（/mcp）を読む
 */
#[Fillable(['name', 'email', 'avatar_url', 'partner_name', 'default_scene', 'cue'])]
#[Hidden(['google_id', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory;

    /** 新しく作るときの既定の値 */
    protected $attributes = [
        'default_scene' => 'daily',
    ];

    /** 列の型（場面は列挙、同意の日時は日時）を決める */
    protected function casts(): array
    {
        return [
            'default_scene' => Scene::class,
            'agreed_at' => 'datetime',
        ];
    }

    /** 利用規約・プライバシーポリシー・年齢の確認に同意したかを返す（していなければ同意の画面へ送る） */
    public function hasAgreed(): bool
    {
        return $this->agreed_at !== null;
    }

    /** 相棒の名前が入っているか（相棒モードを使えるか）を返す */
    public function hasPartner(): bool
    {
        return filled($this->partner_name);
    }

    /**
     * 子ども向けの言葉（まちがいノート・ぼくの図鑑・「かも」・見に行く日など）を出す人かを返す。
     * 決まりは1つ：ふだんの場面（設定）が「親子」の人に出す（事件ごとの場面では切り替えない）。手引きの「大人向け・子ども向け」の
     * ボタンで選んだときだけは、その選択が先（?kid=1・?kid=0）
     */
    public function prefersKidWords(): bool
    {
        return $this->default_scene === Scene::Family;
    }

    /** 相棒の呼び名を返す。入っていなければ「相棒」 */
    public function partnerLabel(): string
    {
        return $this->hasPartner() ? $this->partner_name : config('suiri.labels.words.partner');
    }

    /** この人のタスク */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** この人の事件 */
    public function caseFiles(): HasMany
    {
        return $this->hasMany(CaseFile::class);
    }

    /** この人の手がかり（すべての事件の分） */
    public function clues(): HasMany
    {
        return $this->hasMany(Clue::class);
    }

    /** この人の見立て（すべての事件の分） */
    public function hypotheses(): HasMany
    {
        return $this->hasMany(Hypothesis::class);
    }

    /** この人の予想（すべての事件の分） */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /** この人の屋根裏の棚 */
    public function atticNotes(): HasMany
    {
        return $this->hasMany(AtticNote::class);
    }

    /** この人の週のふりかえり */
    public function weeklyReflections(): HasMany
    {
        return $this->hasMany(WeeklyReflection::class);
    }

    /** この人の名推理カードへの推理 */
    public function sceneAttempts(): HasMany
    {
        return $this->hasMany(SceneAttempt::class);
    }

    /** この人が閉じたワトソンのひとこと */
    public function dismissedHints(): HasMany
    {
        return $this->hasMany(DismissedHint::class);
    }

    /** この人の Google カレンダー連携（1人1行。つないでいなければ null） */
    public function calendarConnection(): HasOne
    {
        return $this->hasOne(CalendarConnection::class);
    }
}
