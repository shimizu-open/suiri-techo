<?php

namespace Tests\Feature\Cases;

use App\Actions\Cases\ResolvePrediction;
use App\Actions\Cases\WithdrawPrediction;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 答え合わせ（C-7・C-8）：最初の予想と書いた日時を見せる・4つの結果・外れの原因・確かめるタスクの完了・見立ての直し */
class ResolvePredictionTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /**
     * 答え合わせの画面に、書いた日時・最初の予想の文・確かさ（変えられないことの一言）を先に出し、
     * その下に「実際はどうだった？」と4つの結果のボタン・外れのときの「どこで見落とした？」・個人情報の注意書きが出ることを確かめる
     */
    public function test_page_shows_the_original_prediction_and_its_time(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        $w = config('suiri.resolve');

        $this->actingAs($user)->get(route('predictions.show', $prediction))->assertOk()
            ->assertSeeInOrder([__($w['written'], ['date' => $prediction->created_at->isoFormat($w['written_format'])]),
                '前の晩に時間割を見れば、袋が玄関にあるはず', $w['frozen'], '70％（ありそう）',
                $w['detail']['written'], $prediction->created_at->isoFormat($w['detail_format'])])
            ->assertSeeInOrder([$w['question'], '当たり', '外れ', 'どちらとも言えない', '前提が崩れた', $w['miss_label'], $w['save']])
            ->assertSee(config('suiri.notes.privacy'));
    }

    /** ふだんの場面が親子の人には、結果のボタンと見落としの問いを子ども向けの言葉で出すことを確かめる */
    public function test_family_users_see_kid_words(): void
    {
        [$user, , , , $prediction] = $this->board(true);
        $user->update(['default_scene' => 'family']);

        $this->actingAs($user)->get(route('predictions.show', $prediction))->assertOk()
            ->assertSeeInOrder(['あたり', 'はずれ', 'わからない', 'そもそもちがった', 'どこでまちがえた？'])
            ->assertSee('70％（たぶん そう）');
    }

    /** 当たりを記録すると、話し言葉のお知らせが出て、結果と一言と日時が残り、確かめるタスクが済みになり、見立てを「当たり」にすることを勧めることを確かめる */
    public function test_hit_completes_the_task_and_suggests_confirming(): void
    {
        [$user, , , $hypothesis, $prediction] = $this->board(true);

        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'hit', 'result_note' => '袋があった'])
            ->assertRedirect(route('predictions.show', $prediction))->assertSessionHas('status', config('suiri.resolve.saved'));

        $prediction->refresh();
        $this->assertSame(['hit', '袋があった'], [$prediction->result->value, $prediction->result_note]);
        $this->assertNotNull($prediction->resolved_at);
        $this->assertTrue(Task::firstOrFail()->isDone());
        $this->actingAs($user)->get(route('predictions.show', $prediction))
            ->assertSee(config('suiri.ui.stamp_top').'：当たり')->assertSee('見立てを「当たり」にする？')
            ->assertSee('name="status" value="confirmed"', false)->assertDontSee('id="resolve-form"', false);

        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), ['status' => 'confirmed'])->assertRedirect();
        $this->assertSame('confirmed', $hypothesis->refresh()->status->value);
    }

    /** 外れのときは「何を見落とした？」が要り、選ぶと残って、見立てを「消えた」にすることを勧めることを確かめる */
    public function test_miss_needs_a_reason_and_suggests_eliminating(): void
    {
        [$user, , , , $prediction] = $this->board(true);

        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'miss'])
            ->assertSessionHasErrors('miss_reason');
        $this->assertTrue($prediction->refresh()->isPending());

        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'miss', 'miss_reason' => 'assumption'])->assertRedirect();
        $this->assertSame('assumption', $prediction->refresh()->miss_reason->value);
        $this->actingAs($user)->get(route('predictions.show', $prediction))
            ->assertSee('思い込みがあった')->assertSee('見立てを「消えた」にする？')->assertSee('ノーベリ帳');
    }

    /** 外れ以外の結果では原因を残さず、どちらとも言えないときは新しい予想を立てることを勧め、そのリンクで事件の画面の「もしそうなら」のカードが開くことを確かめる */
    public function test_unclear_drops_the_reason_and_suggests_a_new_prediction(): void
    {
        [$user, , , $hypothesis, $prediction] = $this->board(true);

        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'unclear', 'miss_reason' => 'chance']);

        $this->assertNull($prediction->refresh()->miss_reason);
        $this->actingAs($user)->get(route('predictions.show', $prediction))
            ->assertSee('新しい予想を立てる')->assertSee('open=predict-'.$hypothesis->id, false);
        // そのリンクの先（事件の画面）では、その見立ての「もしそうなら」のカードが開く
        $this->get(route('cases.show', ['case' => $hypothesis->case_file_id, 'open' => 'predict-'.$hypothesis->id]))
            ->assertSee('action="'.route('predictions.store', $hypothesis).'"', false);
    }

    /** 答え合わせ済み・取り下げた予想は、もう一度答え合わせできないことを確かめる */
    public function test_cannot_resolve_twice_or_after_withdrawal(): void
    {
        [$user, , , , $prediction] = $this->board(true);

        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'hit']);
        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'miss', 'miss_reason' => 'chance'])->assertForbidden();
        $this->assertSame('hit', $prediction->refresh()->result->value);

        [$other, , , , $withdrawn] = $this->board(true);
        $this->actingAs($other)->post(route('predictions.withdraw', $withdrawn));
        $this->actingAs($other)->post(route('predictions.resolve', $withdrawn), ['result' => 'hit'])->assertForbidden();
        $this->actingAs($other)->get(route('predictions.show', $withdrawn))->assertSee('取り下げたよ')->assertDontSee('id="resolve-form"', false);
    }

    /**
     * 答え合わせの用事に、取り下げより前に読んだ古い予想（手元では答え合わせ待ちに見える）を渡しても、
     * 鍵をかけて読み直すので何もせず false を返し、取り下げが残ることを確かめる（答え合わせと取り下げが同時に来たときの守り）
     */
    public function test_the_resolve_action_rereads_with_a_lock_and_ignores_withdrawn_predictions(): void
    {
        [, , , , $prediction] = $this->board(true);
        $stale = $prediction->fresh();
        app(WithdrawPrediction::class)->handle($prediction);

        $this->assertTrue($stale->isPending());
        $this->assertFalse(app(ResolvePrediction::class)->handle($stale, ['result' => 'hit']));
        $prediction->refresh();
        $this->assertNull($prediction->result);
        $this->assertNotNull($prediction->withdrawn_at);
    }

    /** 同じ予想の答え合わせを二度続けて用事に渡しても、先に書いた結果が残ることを確かめる（二重の送信の守り） */
    public function test_the_resolve_action_keeps_the_first_result(): void
    {
        [, , , , $prediction] = $this->board(true);
        $stale = $prediction->fresh();

        $this->assertTrue(app(ResolvePrediction::class)->handle($prediction, ['result' => 'hit']));
        $this->assertFalse(app(ResolvePrediction::class)->handle($stale, ['result' => 'miss', 'miss_reason' => 'chance']));
        $this->assertSame('hit', $prediction->refresh()->result->value);
    }
}
