<?php

namespace Tests\Feature\Calendar;

use App\Enums\CalendarStatus;
use App\Jobs\PushAllTasksToCalendar;
use App\Models\CalendarConnection;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

/** カレンダーとつなぐ流れ（説明の画面 → Google へ → 戻り）。Google の戻りは Socialite の偽物、カレンダーは偽の CalendarApi */
class ConnectCalendarTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** テストごとに Google の鍵を入れ、キューは積むだけにする */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureGoogle();
        Queue::fake();
    }

    /** Google から戻ってきた許可の偽物を用意する（既定は、すべてのスコープと refresh token がそろった正しい許可） */
    private function fakeGoogle(User $user, array $overrides = []): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'id' => $user->google_id,
            'email' => $user->email,
            'token' => 'access-123',
            'refreshToken' => 'refresh-456',
            'expiresIn' => 3599,
            'approvedScopes' => ['openid', ...config('suiri.calendar.scopes')],
            ...$overrides,
        ]));
    }

    /** 説明の画面に、送るもの・送らないもの・読むもの・米国の会社・やめ方と、同意して進むボタンが出ることを確かめる */
    public function test_the_consent_page_explains_before_connecting(): void
    {
        $this->actingAs(User::factory()->create())->get(route('calendar.connect'))->assertOk()
            ->assertSee(config('suiri.calendar.consent.send'))
            ->assertSee(config('suiri.calendar.consent.not_send'))
            ->assertSee(config('suiri.calendar.consent.receive'))
            ->assertSee(config('suiri.calendar.consent.company'))
            ->assertSee(config('suiri.calendar.consent.stop'))
            ->assertSee(route('calendar.redirect'))
            ->assertSee('同意して Google へ進む');
    }

    /** Google の鍵が未設定なら「準備中」と出て、進むボタンがなく、送り出しと戻りも設定の画面へ戻されることを確かめる */
    public function test_it_shows_preparing_when_keys_are_missing(): void
    {
        config(['services.google.client_id' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('calendar.connect'))->assertOk()->assertSee('準備中')->assertDontSee(route('calendar.redirect'));
        $this->actingAs($user)->post(route('calendar.redirect'))->assertRedirect(route('settings.show'))->assertSessionHas('error');
        $this->actingAs($user)->get(route('calendar.callback'))->assertRedirect(route('settings.show'))->assertSessionHas('error');
    }

    /** 送り出しは、カレンダーのスコープ・offline・consent・これまでの許可を含める・login_hint・PKCE を付けて Google へ送ることを確かめる */
    public function test_redirect_asks_for_offline_calendar_access(): void
    {
        $user = User::factory()->create(['email' => 'holmes@example.com']);

        $location = $this->actingAs($user)->post(route('calendar.redirect'))->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://accounts.google.com/', $location);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertSame('true', $query['include_granted_scopes']);
        $this->assertSame('holmes@example.com', $query['login_hint']);
        $this->assertSame(route('calendar.callback'), $query['redirect_uri']);
        $this->assertArrayHasKey('code_challenge', $query);
        // スコープは、ログインの3つ（Socialite の既定）にカレンダーの2つを足したもの
        foreach (['openid', ...config('suiri.calendar.scopes')] as $scope) {
            $this->assertContains($scope, explode(' ', $query['scope']));
        }
    }

    /** 正しい許可で戻ると、トークンを暗号化して保存し、専用カレンダーを作り、まとめて書き出す仕事を出すことを確かめる */
    public function test_callback_connects_and_creates_the_app_calendar(): void
    {
        $user = User::factory()->create();
        $this->fakeGoogle($user);

        $this->actingAs($user)->get(route('calendar.callback'))->assertRedirect(route('settings.show'))->assertSessionHas('status');

        $connection = $user->refresh()->calendarConnection;
        $this->assertSame(CalendarStatus::Active, $connection->status);
        $this->assertSame('access-123', $connection->access_token);
        $this->assertSame('refresh-456', $connection->refresh_token);
        $this->assertSame([config('suiri.calendar.name')], array_values($this->fakeApi()->calendars));
        $this->assertSame(array_key_first($this->fakeApi()->calendars), $connection->app_calendar_id);
        $raw = DB::table('calendar_connections')->first();
        $this->assertStringNotContainsString('refresh-456', $raw->refresh_token);
        $this->assertStringNotContainsString('access-123', $raw->access_token);
        Queue::assertPushed(PushAllTasksToCalendar::class, fn ($job) => $job->userId === $user->id);
    }

    /** ログイン中とは別の Google アカウントで許可されたら、保存せずに断ることを確かめる */
    public function test_a_different_google_account_is_refused(): void
    {
        $user = User::factory()->create();
        $this->fakeGoogle($user, ['id' => 'someone-else']);

        $this->actingAs($user)->get(route('calendar.callback'))->assertRedirect(route('settings.show'))->assertSessionHas('error');

        $this->assertSame(0, CalendarConnection::count());
        $this->assertSame([], $this->fakeApi()->calendars);
        Queue::assertNothingPushed();
    }

    /** カレンダーのスコープの一部が外されていたら、保存せずに断ることを確かめる */
    public function test_missing_scopes_are_refused(): void
    {
        $user = User::factory()->create();
        $this->fakeGoogle($user, ['approvedScopes' => ['openid', config('suiri.calendar.scopes')[0]]]);

        $this->actingAs($user)->get(route('calendar.callback'))->assertSessionHas('error');

        $this->assertSame(0, CalendarConnection::count());
    }

    /** refresh token が返ってこなければ、保存せずに断ることを確かめる */
    public function test_a_missing_refresh_token_is_refused(): void
    {
        $user = User::factory()->create();
        $this->fakeGoogle($user, ['refreshToken' => null]);

        $this->actingAs($user)->get(route('calendar.callback'))->assertSessionHas('error');

        $this->assertSame(0, CalendarConnection::count());
    }

    /** 専用カレンダーを作れない（Google が止まっている）ときは、保存せずに断ることを確かめる */
    public function test_it_is_refused_when_the_calendar_cannot_be_created(): void
    {
        $user = User::factory()->create();
        $this->fakeGoogle($user);
        $this->fakeApi()->failing = true;

        $this->actingAs($user)->get(route('calendar.callback'))->assertSessionHas('error');

        $this->assertSame(0, CalendarConnection::count());
    }

    /** 切れた連携をつなぎ直すと、残っている専用カレンダーをそのまま使い、つながった状態に戻ることを確かめる */
    public function test_reconnecting_reuses_the_existing_calendar(): void
    {
        $user = $this->connectedUser(['status' => 'broken']);
        $calendarId = $user->calendarConnection->app_calendar_id;
        $task = Task::factory()->for($user)->create(['calendar_event_id' => 'keepme']);
        $this->fakeGoogle($user);

        $this->actingAs($user)->get(route('calendar.callback'))->assertSessionHas('status');

        $connection = $user->refresh()->calendarConnection;
        $this->assertSame(CalendarStatus::Active, $connection->status);
        $this->assertSame($calendarId, $connection->app_calendar_id);
        $this->assertCount(1, $this->fakeApi()->calendars);
        $this->assertSame('keepme', $task->refresh()->calendar_event_id);
    }

    /** 前の専用カレンダーが Google で消されていたら、新しく作り、タスクと前の予定のつながりを外すことを確かめる */
    public function test_reconnecting_after_the_calendar_was_deleted_starts_over(): void
    {
        $user = User::factory()->create();
        CalendarConnection::factory()->for($user)->create(['app_calendar_id' => 'deleted@group.calendar.google.com', 'sync_token' => 'old']);
        $task = Task::factory()->for($user)->create(['calendar_event_id' => 'old-event']);
        $this->fakeGoogle($user);

        $this->actingAs($user)->get(route('calendar.callback'))->assertSessionHas('status');

        $connection = $user->refresh()->calendarConnection;
        $this->assertNotSame('deleted@group.calendar.google.com', $connection->app_calendar_id);
        $this->assertNull($connection->sync_token);
        $this->assertNull($task->refresh()->calendar_event_id);
    }

    /** ログインしていない人は、つなぐ画面にも戻り先にも入れないことを確かめる */
    public function test_guests_cannot_connect(): void
    {
        $this->get(route('calendar.connect'))->assertRedirect();
        $this->get(route('calendar.callback'))->assertRedirect();
        $this->delete(route('calendar.disconnect'))->assertRedirect();
        $this->assertSame(0, CalendarConnection::count());
    }
}
