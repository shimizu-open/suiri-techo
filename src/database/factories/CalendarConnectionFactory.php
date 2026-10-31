<?php

namespace Database\Factories;

use App\Models\CalendarConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の Google カレンダー連携を作る（偽のトークン）。broken() で「連携が切れた」状態にする
 *
 * @extends Factory<CalendarConnection>
 */
class CalendarConnectionFactory extends Factory
{
    /** 連携の既定の値を返す */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'expires_at' => now()->addHour(),
            'scopes' => config('suiri.calendar.scopes'),
            'app_calendar_id' => 'fake-calendar@group.calendar.google.com',
            'sync_token' => null,
            'status' => 'active',
        ];
    }

    /** 許可が取り消された・切れた状態にする */
    public function broken(): static
    {
        return $this->state(fn () => ['status' => 'broken']);
    }
}
