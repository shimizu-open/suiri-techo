<?php

namespace Tests\Feature\Calendar;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** 設定の画面の「Google カレンダー」の枠（準備中・つないでいない・つながっている・切れた の出し分け） */
class CalendarPanelTest extends TestCase
{
    use CalendarHelpers, RefreshDatabase;

    /** つないでいない人には、何が起きるかの説明と「カレンダーとつなぐ」（説明の画面へ）が出ることを確かめる */
    public function test_not_connected_offers_to_connect(): void
    {
        $this->configureGoogle();

        $this->actingAs(User::factory()->create())->get(route('settings.show'))->assertOk()
            ->assertSee('つないでいません')
            ->assertSee(route('calendar.connect'))
            ->assertDontSee('action="'.route('calendar.disconnect').'"', false);
    }

    /** Google の鍵が未設定なら「準備中」と出て、つなぐボタンが出ないことを確かめる */
    public function test_missing_keys_show_preparing(): void
    {
        config(['services.google.client_id' => null]);

        $this->actingAs(User::factory()->create())->get(route('settings.show'))->assertOk()
            ->assertSee('準備中')
            ->assertDontSee(route('calendar.connect'));
    }

    /** つながっている人には、状態・最後に同期した日時・やめるボタン（専用カレンダーも消すかの選択つき）が出ることを確かめる */
    public function test_connected_shows_last_sync_and_disconnect(): void
    {
        $this->configureGoogle();
        $user = $this->connectedUser(['last_synced_at' => Carbon::parse('2026-10-31 14:05', 'Asia/Tokyo')]);

        $this->actingAs($user)->get(route('settings.show'))->assertOk()
            ->assertSee(config('suiri.labels.calendar_status.active'))
            ->assertSee('2026年10月31日 14:05')
            ->assertSee('action="'.route('calendar.disconnect').'"', false)
            ->assertSee('name="delete_calendar"', false)
            ->assertDontSee('つなぎ直してください');
    }

    /** 連携が切れた人には「連携が切れました。つなぎ直してください」と、つなぎ直すボタンが出ることを確かめる */
    public function test_broken_asks_to_reconnect(): void
    {
        $this->configureGoogle();
        $user = $this->connectedUser(['status' => 'broken']);

        $this->actingAs($user)->get(route('settings.show'))->assertOk()
            ->assertSee('連携が切れました。つなぎ直してください。')
            ->assertSee(route('calendar.connect'))
            ->assertSee('action="'.route('calendar.disconnect').'"', false)
            ->assertDontSee('name="delete_calendar"', false);
    }
}
