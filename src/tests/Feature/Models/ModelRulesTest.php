<?php

namespace Tests\Feature\Models;

use App\Enums\Fit;
use App\Enums\PredictionResult;
use App\Models\CalendarConnection;
use App\Models\CaseFile;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use App\Models\HypothesisStatusLog;
use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/** モデルの決まり（予想の書き換え禁止・持ち主の写し・トークンの暗号化・小さな判定・ファクトリー） */
class ModelRulesTest extends TestCase
{
    use RefreshDatabase;

    /** 予想の文と確かさは保存後に書き換えられず、答え合わせの結果は書き込めることを確かめる */
    public function test_prediction_statement_and_confidence_are_frozen(): void
    {
        $prediction = Prediction::factory()->create();

        $prediction->update(['result' => 'hit', 'resolved_at' => now()]);
        $this->assertSame(PredictionResult::Hit, $prediction->refresh()->result);

        $this->expectException(LogicException::class);
        $prediction->update(['confidence' => 95]);
    }

    /** 事件や見立てから記録を作ると、user_id（と予想の事件）が親から写されることを確かめる */
    public function test_owner_is_copied_from_the_parent(): void
    {
        $case = CaseFile::factory()->create();
        $clue = $case->clues()->create(['kind' => 'seen', 'body' => '玄関に袋がなかった']);
        $hypothesis = $case->hypotheses()->create(['body' => '時間割を見ていない']);
        $prediction = $hypothesis->predictions()->create(['statement' => '前の晩に見れば忘れない', 'confidence' => 70, 'check_on' => '2026-11-01']);
        $log = HypothesisStatusLog::create(['hypothesis_id' => $hypothesis->id, 'from_status' => 'considering', 'to_status' => 'likely']);

        $this->assertSame($case->user_id, $clue->user_id);
        $this->assertSame($case->user_id, $hypothesis->user_id);
        $this->assertSame($case->id, $prediction->case_file_id);
        $this->assertSame($case->user_id, $prediction->user_id);
        $this->assertSame($case->user_id, $log->user_id);
    }

    /** ○× は見立てと手がかりの両方から引け、Fit の列挙として読めることを確かめる */
    public function test_fits_are_readable_from_both_sides(): void
    {
        $fit = ClueHypothesis::factory()->contradicts()->create();
        $hypothesis = Hypothesis::find($fit->hypothesis_id);

        $this->assertSame(Fit::Contradicts, $hypothesis->clues->first()->pivot->fit);
        $this->assertSame(Fit::Contradicts, $hypothesis->fits->first()->fit);
        $this->assertSame('×', $hypothesis->fits->first()->fit->symbol());
    }

    /** Google のトークンは暗号化して保存され、JSON（toArray）には出ないことを確かめる */
    public function test_calendar_tokens_are_encrypted_and_hidden(): void
    {
        $connection = CalendarConnection::factory()->create(['refresh_token' => 'secret-refresh']);

        $raw = DB::table('calendar_connections')->value('refresh_token');
        $this->assertNotSame('secret-refresh', $raw);
        $this->assertSame('secret-refresh', $connection->refresh()->refresh_token);
        $this->assertArrayNotHasKey('refresh_token', $connection->toArray());
        $this->assertArrayNotHasKey('access_token', $connection->toArray());
    }

    /** タスクと予想の小さな判定（済んだか・答え合わせ待ちか・期日が来たか）を確かめる */
    public function test_small_checks_on_tasks_and_predictions(): void
    {
        $this->assertFalse(Task::factory()->create()->isDone());
        $this->assertTrue(Task::factory()->done()->create()->isDone());

        $due = Prediction::factory()->due()->create();
        $this->assertTrue($due->isPending());
        $this->assertTrue($due->isDue(today()->toDateString()));
        $this->assertFalse(Prediction::factory()->withdrawn()->create()->isPending());
        $this->assertFalse(Prediction::factory()->create()->isDue(today()->toDateString()));

        $task = Task::factory()->verify($due)->create();
        $this->assertTrue($task->isVerify());
        $this->assertTrue($due->task->is($task));
    }

    /** すべてのモデルのファクトリーで記録を作れ、退会（利用者を消す）でその人の記録が消えることを確かめる */
    public function test_factories_work_and_user_deletion_cascades(): void
    {
        // モデルの名前を1つずつ取り出して、ファクトリーで1件ずつ作る
        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            $this->assertNotNull($class::factory()->create()->getKey(), $class);
        }

        $user = User::factory()->create();
        Prediction::factory()->for(CaseFile::factory()->for($user), 'caseFile')->create([
            'hypothesis_id' => Hypothesis::factory()->for(CaseFile::factory()->for($user), 'caseFile')->create()->id,
        ]);
        $user->delete();
        $this->assertSame(0, CaseFile::where('user_id', $user->id)->count());
    }
}
