<?php

namespace Tests\Feature\Account;

use App\Models\AtticNote;
use App\Models\CalendarConnection;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use App\Models\HypothesisStatusLog;
use App\Models\Prediction;
use App\Models\SceneAttempt;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyReflection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** データのダウンロード（GET /settings/export）：自分のデータがすべて入り、トークンと他人のデータは入らない */
class ExportUserDataTest extends TestCase
{
    use RefreshDatabase;

    /** その人の記録を、表ごとに見分けのつく文で1つずつ作る */
    private function fill(User $user, string $tag): void
    {
        Task::factory()->for($user)->create(['title' => "{$tag}のタスク"]);
        $case = CaseFile::factory()->for($user)->create(['title' => "{$tag}の事件"]);
        $clue = Clue::factory()->create(['case_file_id' => $case->id, 'body' => "{$tag}の手がかり"]);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => "{$tag}の見立て"]);
        ClueHypothesis::factory()->create(['hypothesis_id' => $hypothesis->id, 'clue_id' => $clue->id, 'fit' => 'contradicts']);
        HypothesisStatusLog::factory()->create(['hypothesis_id' => $hypothesis->id, 'to_status' => 'eliminated']);
        Prediction::factory()->create(['hypothesis_id' => $hypothesis->id, 'statement' => "{$tag}の予想"]);
        AtticNote::factory()->for($user)->create(['rule' => "{$tag}の知識"]);
        WeeklyReflection::factory()->for($user)->create(['abstraction' => "{$tag}のふりかえり"]);
        SceneAttempt::factory()->for($user)->create(['answer' => "{$tag}の推理"]);
        CalendarConnection::factory()->for($user)->create([
            'access_token' => "{$tag}-access-token", 'refresh_token' => "{$tag}-refresh-token", 'app_calendar_id' => "{$tag}@group.calendar.google.com",
        ]);
    }

    /** ダウンロードは日付入りのファイル名の JSON で、自分の表のデータがすべて入ることを確かめる */
    public function test_export_contains_all_own_data(): void
    {
        $this->travelTo('2026-10-31 09:00:00');
        $user = User::factory()->create(['name' => 'ワトソン']);
        $this->fill($user, '自分');

        $response = $this->actingAs($user)->get(route('settings.export'))->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="suiri-techo-2026-10-31.json"');
        $json = $response->json();

        $this->assertSame('ワトソン', $json['user']['name']);
        $this->assertSame('自分のタスク', $json['tasks'][0]['title']);
        $this->assertSame('自分の事件', $json['cases'][0]['title']);
        $this->assertSame('自分の手がかり', $json['cases'][0]['clues'][0]['body']);
        $this->assertSame('自分の見立て', $json['cases'][0]['hypotheses'][0]['body']);
        $this->assertSame('contradicts', $json['cases'][0]['hypotheses'][0]['fits'][0]['fit']);
        $this->assertSame('eliminated', $json['cases'][0]['hypotheses'][0]['status_logs'][0]['to_status']);
        $this->assertSame('自分の予想', $json['cases'][0]['predictions'][0]['statement']);
        $this->assertSame('自分の知識', $json['attic_notes'][0]['rule']);
        $this->assertSame('自分のふりかえり', $json['weekly_reflections'][0]['abstraction']);
        $this->assertSame('自分の推理', $json['scene_attempts'][0]['answer']);
        $this->assertSame(['connected' => true, 'app_calendar_id' => '自分@group.calendar.google.com'], $json['calendar']);
    }

    /** ダウンロードに Google のトークン・Google の利用者 ID・他人のデータが入らないことを確かめる */
    public function test_export_has_no_tokens_and_no_other_users_data(): void
    {
        $user = User::factory()->create();
        $this->fill($user, '自分');
        $this->fill(User::factory()->create(), '他人');

        $body = $this->actingAs($user)->get(route('settings.export'))->assertOk()->getContent();

        // トークンの文字・列の名前、Google の ID、他人の記録が1つも入っていないことを確かめる
        foreach (['自分-access-token', '自分-refresh-token', 'access_token', 'refresh_token', 'sync_token', 'remember_token', $user->google_id, '他人'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
        $this->assertStringContainsString('自分の事件', $body);
    }

    /** カレンダーとつないでいなければ connected は false で、専用カレンダーの ID は null になることを確かめる */
    public function test_export_without_calendar(): void
    {
        $user = User::factory()->create();

        $json = $this->actingAs($user)->get(route('settings.export'))->assertOk()->json();

        $this->assertSame(['connected' => false, 'app_calendar_id' => null], $json['calendar']);
        $this->assertSame([], $json['cases']);
    }

    /** ログインしていない人はダウンロードできず、トップへ送られることを確かめる */
    public function test_guests_cannot_export(): void
    {
        $this->get(route('settings.export'))->assertRedirect(route('home'));
    }
}
