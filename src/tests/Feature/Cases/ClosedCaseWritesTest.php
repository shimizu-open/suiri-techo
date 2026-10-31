<?php

namespace Tests\Feature\Cases;

use App\Models\Clue;
use App\Models\Hypothesis;
use App\Models\Prediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/**
 * 閉じた（解決・迷宮入り）事件への書き込みを、受付でも断る（画面にボタンがないだけにしない）。
 * 手がかり・見立て・○×・予想・見せ合うは「開き直してください」の 403。答え合わせと取り下げは閉じた事件でもできる
 */
class ClosedCaseWritesTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 閉じた事件の手がかりを足す・直す・消す送信が、開き直すよう伝える 403 になり、何も変わらないことを確かめる */
    public function test_clues_of_a_closed_case_cannot_be_changed(): void
    {
        [$user, $case, $clue] = $this->board(false, ['status' => 'solved', 'closed_at' => now()]);
        $message = config('suiri.cases.closed_message');

        $this->actingAs($user)->post(route('clues.store', $case), ['kind' => 'seen', 'body' => 'あとから'])
            ->assertForbidden()->assertSee($message);
        $this->actingAs($user)->patch(route('clues.update', $clue), ['kind' => 'heard', 'body' => '書き換え'])->assertForbidden();
        $this->actingAs($user)->delete(route('clues.destroy', $clue))->assertForbidden();

        $this->assertSame(1, Clue::count());
        $this->assertSame('玄関に体操着の袋がなかった', $clue->refresh()->body);
    }

    /** 閉じた事件の見立てを足す・直す（本文も状態も）・消す送信が 403 になり、何も変わらないことを確かめる */
    public function test_hypotheses_of_a_closed_case_cannot_be_changed(): void
    {
        [$user, $case, , $hypothesis] = $this->board(false, ['status' => 'cold', 'closed_at' => now()]);

        $this->actingAs($user)->post(route('hypotheses.store', $case), ['body' => 'あとから'])->assertForbidden();
        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), ['body' => '書き換え'])->assertForbidden();
        $this->actingAs($user)->patch(route('hypotheses.update', $hypothesis), ['status' => 'confirmed'])
            ->assertForbidden()->assertSee(config('suiri.cases.closed_message'));
        $this->actingAs($user)->delete(route('hypotheses.destroy', $hypothesis))->assertForbidden();

        $hypothesis->refresh();
        $this->assertSame(1, Hypothesis::count());
        $this->assertSame(['前の晩に時間割を見ていない', 'considering'], [$hypothesis->body, $hypothesis->status->value]);
    }

    /** 閉じた事件では ○× を付ける・外す・予想を立てる・見せ合う送信が 403 になることを確かめる */
    public function test_fits_predictions_and_reveal_are_refused_on_a_closed_case(): void
    {
        [$user, $case, $clue, $hypothesis] = $this->board(false, ['status' => 'solved', 'closed_at' => now(), 'partner_mode' => true]);

        $this->actingAs($user)->put(route('fits.update', [$hypothesis, $clue]), ['fit' => 'fits'])->assertForbidden();
        $this->actingAs($user)->delete(route('fits.destroy', [$hypothesis, $clue]))->assertForbidden();
        $this->actingAs($user)->post(route('predictions.store', $hypothesis), [
            'statement' => 'もしそうなら袋がある', 'confidence' => 70, 'check_on' => today()->toDateString(),
        ])->assertForbidden();
        $this->actingAs($user)->post(route('cases.reveal', $case))->assertForbidden();

        $this->assertSame(0, $hypothesis->fits()->count());
        $this->assertSame(0, Prediction::count());
        $this->assertNull($case->refresh()->revealed_at);
    }

    /** 開き直せば、同じ送信がまた通ることを確かめる（閉じている間だけの止め） */
    public function test_reopening_lets_the_writes_through_again(): void
    {
        [$user, $case] = $this->board(false, ['status' => 'solved', 'closed_at' => now()]);

        $this->actingAs($user)->delete(route('cases.reopen', $case))->assertRedirect();
        $this->actingAs($user)->post(route('clues.store', $case), ['kind' => 'seen', 'body' => '開き直してから'])->assertRedirect();

        $this->assertSame(2, Clue::count());
    }

    /** 待っている予想は、事件を閉じたあとでも答え合わせと取り下げができることを確かめる（結果を残す・確かめるタスクを片づけるため） */
    public function test_pending_predictions_can_still_be_resolved_or_withdrawn_after_closing(): void
    {
        [$user, $case, , , $prediction] = $this->board(true);
        $case->update(['status' => 'solved', 'closed_at' => now()]);

        $this->actingAs($user)->post(route('predictions.resolve', $prediction), ['result' => 'hit'])->assertRedirect();
        $this->assertSame('hit', $prediction->refresh()->result->value);

        [$other, $otherCase, , , $pending] = $this->board(true);
        $otherCase->update(['status' => 'cold', 'closed_at' => now()]);
        $this->actingAs($other)->post(route('predictions.withdraw', $pending))->assertRedirect();
        $this->assertNotNull($pending->refresh()->withdrawn_at);
    }

    /** 他人の閉じた事件に送っても、閉じているかどうかを知らせない 403 になることを確かめる */
    public function test_another_users_closed_case_does_not_reveal_its_state(): void
    {
        [, $case] = $this->board(false, ['status' => 'solved', 'closed_at' => now()]);

        $this->actingAs($this->member())->post(route('clues.store', $case), ['kind' => 'seen', 'body' => 'のぞき'])
            ->assertForbidden()->assertDontSee(config('suiri.cases.closed_message'));
    }
}
