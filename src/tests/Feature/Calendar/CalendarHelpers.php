<?php

namespace Tests\Feature\Calendar;

use App\Contracts\CalendarApi;
use App\Models\CalendarConnection;
use App\Models\User;
use App\Services\Google\FakeCalendarApi;
use Illuminate\Support\Str;

/**
 * カレンダーのテストの共通の準備。テストでは CalendarApi に偽物（メモリに書くだけ）が結ばれているので、それを取り出して使う
 */
trait CalendarHelpers
{
    /** テスト用の偽の Google カレンダーを返す（CalendarServiceProvider が1つだけ作って結んでいる） */
    protected function fakeApi(): FakeCalendarApi
    {
        return app(CalendarApi::class);
    }

    /** カレンダーとつないだ利用者を作る。専用カレンダーは人ごとに別の ID にし、偽の Google にもあることにする */
    protected function connectedUser(array $attributes = []): User
    {
        $calendarId = 'cal-'.Str::lower(Str::random(8)).'@group.calendar.google.com';
        $connection = CalendarConnection::factory()->create(['app_calendar_id' => $calendarId, ...$attributes]);
        $this->fakeApi()->calendars[$connection->app_calendar_id] = config('suiri.calendar.name');

        return $connection->user;
    }

    /** Google の鍵（試し用の値）を設定する */
    protected function configureGoogle(): void
    {
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret']);
    }
}
