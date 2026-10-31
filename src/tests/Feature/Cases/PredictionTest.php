<?php

namespace Tests\Feature\Cases;

use App\Actions\Cases\ResolvePrediction;
use App\Actions\Cases\WithdrawPrediction;
use App\Models\AtticNote;
use App\Models\Prediction;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 予想を立てる・取り下げる（C-6）。文と確かさは保存後に直せない。保存すると確かめるタスクができる */
class PredictionTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 予想を立てる送信の形（確かめる日は明日） */
    private function payload(array $override = []): array
    {
        return $override + [
            'statement' => '前の晩に時間割を見れば、袋が玄関にあるはず', 'confidence' => 80,
            'check_on' => today()->addDay()->toDateString(), 'check_at' => '07:30', 'form_key' => 'predict-1',
        ];
    }

    /** 予想を立てると、確かめるタスク（kind=verify・期日・時刻・予想へのつながり）ができることを確かめる */
    public function test_store_creates_a_verify_task(): void
    {
        [$user, $case, , $hypothesis] = $this->board();

        $this->actingAs($user)->post(route('predictions.store', $hypothesis), $this->payload())
            ->assertRedirect(route('cases.show', $case).'#hyp-'.$hypothesis->id);

        $prediction = Prediction::firstOrFail();
        $this->assertSame([$user->id, $case->id, 80], [$prediction->user_id, $prediction->case_file_id, $prediction->confidence]);
        $task = Task::firstOrFail();
        $this->assertSame(['verify', $prediction->check_on, '07:30', $prediction->id, $user->id],
            [$task->kind->value, $task->due_on, $task->starts_at, $task->prediction_id, $task->user_id]);
        $this->assertStringStartsWith('確かめる：', $task->title);
        $this->assertLessThanOrEqual(100, mb_strlen($task->title));
    }

    /** 確かさは選択肢（50〜95の5刻み）だけ、確かめる日は今日以降、屋根裏の知識は自分の棚のものだけを受け付けることを確かめる */
    public function test_store_validation(): void
    {
        [$user, , , $hypothesis] = $this->board();
        $othersNote = AtticNote::factory()->create();
        $myNote = AtticNote::factory()->for($user)->create();

        $this->actingAs($user)->post(route('predictions.store', $hypothesis), $this->payload(['confidence' => 100]))->assertSessionHasErrors('confidence');
        $this->actingAs($user)->post(route('predictions.store', $hypothesis), $this->payload(['confidence' => 72]))->assertSessionHasErrors('confidence');
        $this->actingAs($user)->post(route('predictions.store', $hypothesis), $this->payload(['check_on' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors('check_on');
        $this->actingAs($user)->post(route('predictions.store', $hypothesis), $this->payload(['attic_note_id' => $othersNote->id]))
            ->assertSessionHasErrors('attic_note_id');
        $this->assertSame(0, Prediction::count());

        $this->actingAs($user)->post(route('predictions.store', $hypothesis), $this->payload(['attic_note_id' => $myNote->id]))->assertSessionHasNoErrors();
        $this->assertSame($myNote->id, Prediction::first()->attic_note_id);
    }

    /** 予想の文と確かさを直すルートはなく、モデルで書き換えようとしても止まることを確かめる */
    public function test_statement_and_confidence_are_frozen(): void
    {
        [$user, , , , $prediction] = $this->board(true);

        $this->assertFalse(Route::has('predictions.update'));
        $this->assertFalse(Route::has('predictions.edit'));
        $this->actingAs($user)->patch('/predictions/'.$prediction->id, ['statement' => '後知恵', 'confidence' => 95])->assertStatus(405);

        $this->expectException(LogicException::class);
        $prediction->update(['statement' => '後知恵']);
    }

    /** 取り下げると取り下げた日時が入り、文は残ったまま、済んでいない確かめるタスクは消えることを確かめる */
    public function test_withdraw_removes_the_open_verify_task(): void
    {
        [$user, $case, , , $prediction] = $this->board(true);

        $this->actingAs($user)->post(route('predictions.withdraw', $prediction))->assertRedirect();

        $prediction->refresh();
        $this->assertNotNull($prediction->withdrawn_at);
        $this->assertSame('前の晩に時間割を見れば、袋が玄関にあるはず', $prediction->statement);
        $this->assertSame(0, Task::count());
        $this->actingAs($user)->get(route('cases.show', $case))->assertSee('取り下げ');
    }

    /** 答え合わせ済み・取り下げ済みの予想は取り下げられず（403）、取り下げた日時が入らないことを確かめる（ノーベリ帳と確かさの集計を食い違わせない） */
    public function test_only_pending_predictions_can_be_withdrawn(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'miss', 'miss_reason' => 'chance']);

        $this->actingAs($user)->post(route('predictions.withdraw', $prediction))->assertForbidden();
        $this->assertNull($prediction->refresh()->withdrawn_at);
        $this->assertSame('miss', $prediction->result->value);

        [$other, , , , $withdrawn] = $this->board(true);
        $this->actingAs($other)->post(route('predictions.withdraw', $withdrawn))->assertRedirect();
        $first = $withdrawn->refresh()->withdrawn_at;
        $this->travel(1)->hours();
        $this->actingAs($other)->post(route('predictions.withdraw', $withdrawn))->assertForbidden();
        $this->assertTrue($first->equalTo($withdrawn->refresh()->withdrawn_at));
    }

    /** 取り下げる用事を答え合わせ済みの予想に直接使っても、何もせず false を返し、済んだ確かめるタスクも残ることを確かめる（受付を通らない呼び方の守り） */
    public function test_the_withdraw_action_ignores_resolved_predictions(): void
    {
        [, , , , $prediction] = $this->board(true);
        app(ResolvePrediction::class)->handle($prediction, ['result' => 'hit']);

        $this->assertFalse(app(WithdrawPrediction::class)->handle($prediction->refresh()));
        $this->assertNull($prediction->refresh()->withdrawn_at);
        $this->assertSame(1, Task::count());
    }

    /** 予想の欄に「保存したら直せない」ことが書いてあり、閉じた事件では予想を立てられないことを確かめる */
    public function test_form_warns_and_closed_cases_refuse(): void
    {
        [$user, $case, , $hypothesis] = $this->board();

        $this->actingAs($user)->get(route('cases.show', $case))->assertSee(config('suiri.case_board.cards.predict.lock'));

        $case->update(['status' => 'solved', 'closed_at' => now()]);
        $this->actingAs($user)->post(route('predictions.store', $hypothesis), $this->payload())->assertForbidden();
    }
}
