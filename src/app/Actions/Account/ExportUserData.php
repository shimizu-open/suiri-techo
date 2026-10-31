<?php

namespace App\Actions\Account;

use App\Models\User;
use App\Queries\Ai\ConnectedAppsQuery;

/**
 * 自分のデータをすべて書き出す用事（A-5）。返した配列を受付が JSON のファイルにしてダウンロードさせる。
 * 中身：利用者・タスク・事件（手がかり・見立てと○×・状態を変えた記録・予想・閉じたひとこと）・屋根裏の棚・週のふりかえり・名推理カードへの推理・
 * カレンダー連携（つながっているかと専用カレンダーの ID だけ。トークンは出さない）・
 * つないでいる AI（名前・戻る先の住所・最後につながった日時だけ。通行証の番号は出さない）。
 * 新しい表・列を足したら、ここにも足す（.claude/rules/privacy.md）
 */
class ExportUserData
{
    /** つないでいる AI の一覧を組み立てる係を受け取る */
    public function __construct(private readonly ConnectedAppsQuery $connectedApps) {}

    /** その人のデータを、表ごとに配列にして返す。他人の記録は入らない（すべてこの人の関係からたどる） */
    public function handle(User $user): array
    {
        $connection = $user->calendarConnection()->first();

        return [
            'app' => config('app.name'),
            'exported_at' => now()->toIso8601String(),
            // google_id と remember_token はモデルの Hidden で出ない
            'user' => $user->toArray(),
            'tasks' => $user->tasks()->oldest('id')->get()->toArray(),
            'cases' => $user->caseFiles()->oldest('id')->with([
                'clues',
                'hypotheses.fits',
                'hypotheses.statusLogs',
                'predictions' => fn ($query) => $query->oldest('id'),
                'dismissedHints',
            ])->get()->toArray(),
            'attic_notes' => $user->atticNotes()->oldest('id')->get()->toArray(),
            'weekly_reflections' => $user->weeklyReflections()->oldest('week_start')->get()->toArray(),
            'scene_attempts' => $user->sceneAttempts()->oldest('id')->get()->toArray(),
            // トークンは決して書き出さない（列を1つずつ選ぶ）
            'calendar' => [
                'connected' => $connection !== null && $connection->isActive(),
                'app_calendar_id' => $connection?->app_calendar_id,
            ],
            // つないでいる AI。通行証の番号や中身は決して書き出さない（名前・住所・日時だけを選ぶ）
            'ai_connections' => array_map(fn (array $app) => [
                'name' => $app['name'],
                'redirect_host' => $app['host'],
                'last_connected_at' => $app['last_at']?->toIso8601String(),
            ], $this->connectedApps->for($user)),
        ];
    }
}
