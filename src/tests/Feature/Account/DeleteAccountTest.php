<?php

namespace Tests\Feature\Account;

use App\Models\AtticNote;
use App\Models\CalendarConnection;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\DismissedHint;
use App\Models\Hypothesis;
use App\Models\HypothesisStatusLog;
use App\Models\Invitation;
use App\Models\Prediction;
use App\Models\SceneAttempt;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyReflection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** 退会（DELETE /account）：確認の言葉・すべての表からその人の行が消えること・ログアウト */
class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    /** user_id を持つ、利用者の記録の表 */
    private const OWNED_TABLES = [
        'tasks', 'case_files', 'clues', 'hypotheses', 'hypothesis_status_logs', 'predictions',
        'attic_notes', 'weekly_reflections', 'scene_attempts', 'dismissed_hints', 'calendar_connections',
    ];

    /** その人の記録を、すべての表に1行以上作る（招待とログインの記録も） */
    private function fill(User $user): void
    {
        $prediction = Prediction::factory()->create([
            'hypothesis_id' => Hypothesis::factory()->create(['case_file_id' => CaseFile::factory()->for($user)->create()->id])->id,
        ]);
        $hypothesis = $prediction->hypothesis;
        Task::factory()->verify($prediction)->create();
        $clue = Clue::factory()->create(['case_file_id' => $hypothesis->case_file_id]);
        ClueHypothesis::factory()->create(['hypothesis_id' => $hypothesis->id, 'clue_id' => $clue->id]);
        HypothesisStatusLog::factory()->create(['hypothesis_id' => $hypothesis->id]);
        DismissedHint::factory()->create(['case_file_id' => $hypothesis->case_file_id]);
        AtticNote::factory()->for($user)->create();
        WeeklyReflection::factory()->for($user)->create();
        SceneAttempt::factory()->for($user)->create();
        CalendarConnection::factory()->for($user)->create(['refresh_token' => 'refresh-of-'.$user->id]);
        Invitation::factory()->create(['email' => $user->email, 'used_at' => now()]);
        DB::table('sessions')->insert(['id' => 'sess-'.$user->id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    }

    /** テストごとの準備：Google への送信を偽物にする（本物の Google には届かない） */
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    /** 確認の言葉が空・ちがうときは退会せず、何も消えないことを確かめる */
    public function test_wrong_confirmation_keeps_everything(): void
    {
        $user = User::factory()->create();
        $this->fill($user);

        $this->actingAs($user)->from(route('settings.show'))->delete(route('account.destroy'), ['confirmation' => ''])
            ->assertRedirect(route('settings.show'))->assertSessionHasErrors('confirmation');
        $this->actingAs($user)->delete(route('account.destroy'), ['confirmation' => 'たいかい'])->assertSessionHasErrors('confirmation');

        $this->assertModelExists($user);
        $this->assertDatabaseHas('case_files', ['user_id' => $user->id]);
        $this->assertAuthenticatedAs($user);
    }

    /** 退会すると、すべての表からその人の行が消え、ほかの人の行は残り、ログアウトしてトップへ戻ることを確かめる */
    public function test_delete_removes_every_row_of_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->fill($user);
        $this->fill($other);
        $hypothesisIds = $user->hypotheses()->pluck('id')->all();

        $this->actingAs($user)->delete(route('account.destroy'), ['confirmation' => ' 退会する '])
            ->assertRedirect(route('home'))->assertSessionHas('status');

        $this->assertGuest();
        $this->assertModelMissing($user);
        // user_id を持つ表を1つずつ取り出し、その人の行が消えて、ほかの人の行は残っていることを確かめる
        foreach (self::OWNED_TABLES as $table) {
            $this->assertDatabaseMissing($table, ['user_id' => $user->id]);
            $this->assertDatabaseHas($table, ['user_id' => $other->id]);
        }
        $this->assertSame(0, DB::table('clue_hypothesis')->whereIn('hypothesis_id', $hypothesisIds)->count());
        $this->assertSame(1, DB::table('clue_hypothesis')->count());
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('invitations', ['email' => $user->email]);
        $this->assertDatabaseHas('invitations', ['email' => $other->email]);
        Http::assertSent(fn (Request $request) => $request->url() === config('suiri.calendar.urls.revoke')
            && $request['token'] === 'refresh-of-'.$user->id);
        Http::assertSentCount(1);
    }

    /** Google が止まっていて許可の取り消しに失敗しても、退会は最後まで進み、連携の行も消えることを確かめる */
    public function test_delete_completes_even_if_google_fails(): void
    {
        Http::fake(['*' => Http::response('', 503)]);
        $user = User::factory()->create();
        $this->fill($user);

        $this->actingAs($user)->delete(route('account.destroy'), ['confirmation' => '退会する'])->assertRedirect(route('home'));

        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('calendar_connections', ['user_id' => $user->id]);
    }

    /** カレンダーとつないでいない人の退会では、Google に何も送らないことを確かめる */
    public function test_delete_without_calendar_sends_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('account.destroy'), ['confirmation' => '退会する'])->assertRedirect(route('home'));

        $this->assertModelMissing($user);
        Http::assertNothingSent();
    }

    /** 設定の画面に、ダウンロードと退会の枠（確認の言葉の欄つき）が出ることを確かめる */
    public function test_settings_page_shows_the_danger_zone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.show'))->assertOk()
            ->assertSee('データのダウンロードと退会')
            ->assertSee(route('settings.export'), false)
            ->assertSee(route('account.destroy'), false)
            ->assertSee('「退会する」と入力');
    }

    /** ログインしていない人は退会の操作ができず、トップへ送られることを確かめる */
    public function test_guests_cannot_delete(): void
    {
        $user = User::factory()->create();

        $this->delete(route('account.destroy'), ['confirmation' => '退会する'])->assertRedirect(route('home'));

        $this->assertModelExists($user);
    }
}
