<?php

namespace Tests\Feature\Calendar;

use App\Actions\Calendar\DisconnectCalendar;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** つなぐのをやめる（許可の取り消し・行を消す・つながりを外す・専用カレンダーを消すかを選ぶ・他人の連携は触れない） */
class DisconnectCalendarTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** テストごとに、Google の取り消しの住所を偽物にして、キューは積むだけにする */
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['oauth2.googleapis.com/revoke' => Http::response('', 200)]);
        Queue::fake();
    }

    /** やめると、Google の許可を取り消し、連携の行を消し、タスクと予定のつながりを外し、専用カレンダーは残すことを確かめる */
    public function test_disconnecting_revokes_and_forgets_the_tokens(): void
    {
        $user = $this->connectedUser(['refresh_token' => 'refresh-to-revoke']);
        $calendarId = $user->calendarConnection->app_calendar_id;
        $task = Task::factory()->for($user)->create(['calendar_event_id' => 'ev1', 'calendar_synced_at' => now()]);

        $this->actingAs($user)->delete(route('calendar.disconnect'))->assertRedirect(route('settings.show'))->assertSessionHas('status');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://oauth2.googleapis.com/revoke' && $request['token'] === 'refresh-to-revoke');
        $this->assertSame(0, CalendarConnection::count());
        $this->assertNull($task->refresh()->calendar_event_id);
        $this->assertNull($task->calendar_synced_at);
        $this->assertArrayHasKey($calendarId, $this->fakeApi()->calendars);
    }

    /** 「専用カレンダーも消す」を選ぶと、Google の専用カレンダーも消すことを確かめる */
    public function test_the_app_calendar_can_be_deleted_too(): void
    {
        $user = $this->connectedUser();
        $calendarId = $user->calendarConnection->app_calendar_id;

        $this->actingAs($user)->delete(route('calendar.disconnect'), ['delete_calendar' => '1'])->assertRedirect(route('settings.show'));

        $this->assertArrayNotHasKey($calendarId, $this->fakeApi()->calendars);
        $this->assertSame(0, CalendarConnection::count());
    }

    /** Google が止まっていて取り消しや専用カレンダーの削除に失敗しても、手元の鍵は必ず消すことを確かめる */
    public function test_local_tokens_are_removed_even_if_google_fails(): void
    {
        Http::fake(['oauth2.googleapis.com/revoke' => Http::response('', 503)]);
        $user = $this->connectedUser();
        $this->fakeApi()->failing = true;

        $this->actingAs($user)->delete(route('calendar.disconnect'), ['delete_calendar' => '1'])->assertRedirect(route('settings.show'));

        $this->assertSame(0, CalendarConnection::count());
    }

    /** 切れた連携もやめられ、そのときは専用カレンダーを消しに行かないことを確かめる（鍵が使えないため） */
    public function test_a_broken_connection_can_be_removed(): void
    {
        $user = $this->connectedUser(['status' => 'broken']);

        $this->actingAs($user)->delete(route('calendar.disconnect'), ['delete_calendar' => '1'])->assertRedirect(route('settings.show'));

        $this->assertSame(0, CalendarConnection::count());
        $this->assertSame(0, $this->fakeApi()->calls);
    }

    /** やめるのはログイン中の本人の連携だけで、ほかの人の連携・タスクのつながりは残ることを確かめる */
    public function test_another_users_connection_is_untouched(): void
    {
        $me = $this->connectedUser();
        $other = $this->connectedUser();
        $othersTask = Task::factory()->for($other)->create(['calendar_event_id' => 'theirs']);

        $this->actingAs($me)->delete(route('calendar.disconnect'), ['delete_calendar' => '1']);

        $this->assertNotNull($other->calendarConnection()->first());
        $this->assertArrayHasKey($other->calendarConnection->app_calendar_id, $this->fakeApi()->calendars);
        $this->assertSame('theirs', $othersTask->refresh()->calendar_event_id);
    }

    /** つないでいない人がやめても、何も起きず（Google にも聞かず）設定の画面へ戻ることを確かめる。用事は退会からも呼べる */
    public function test_disconnecting_without_a_connection_is_harmless(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('calendar.disconnect'))->assertRedirect(route('settings.show'));

        Http::assertNothingSent();
        $this->assertFalse(app(DisconnectCalendar::class)->handle($user));
    }

    /** トークンは JSON（データのダウンロードなど）に出ないことを確かめる */
    public function test_tokens_never_appear_in_json(): void
    {
        $connection = CalendarConnection::factory()->create(['access_token' => 'secret-access', 'refresh_token' => 'secret-refresh']);

        $json = $connection->toJson();

        $this->assertStringNotContainsString('secret-access', $json);
        $this->assertStringNotContainsString('secret-refresh', $json);
    }
}
