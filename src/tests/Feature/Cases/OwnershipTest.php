<?php

namespace Tests\Feature\Cases;

use App\Models\AtticNote;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Queries\Cases\OpenCasesQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 他人の事件・手がかり・見立て・予想は、見ることも直すことも消すこともできない（すべて 403）。何も変わらない */
class OwnershipTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 他人の記録に向けた操作の一覧（メソッド・ルート名・ルートに渡す記録・送る値） */
    public static function strangerRequests(): array
    {
        return [
            '事件を見る' => ['get', 'cases.show', ['case'], []],
            '事件を直す' => ['patch', 'cases.update', ['case'], ['title' => '乗っ取り', 'scene' => 'daily']],
            '事件を消す' => ['delete', 'cases.destroy', ['case'], []],
            '事件を閉じる' => ['post', 'cases.close', ['case'], ['status' => 'solved']],
            '事件を開き直す' => ['delete', 'cases.reopen', ['case'], []],
            '見せ合う' => ['post', 'cases.reveal', ['case'], []],
            '手がかりを足す' => ['post', 'clues.store', ['case'], ['kind' => 'seen', 'body' => 'x']],
            '手がかりを直す' => ['patch', 'clues.update', ['clue'], ['kind' => 'seen', 'body' => 'x']],
            '手がかりを消す' => ['delete', 'clues.destroy', ['clue'], []],
            '見立てを足す' => ['post', 'hypotheses.store', ['case'], ['body' => 'x']],
            '見立てを直す' => ['patch', 'hypotheses.update', ['hypothesis'], ['status' => 'eliminated']],
            '見立てを消す' => ['delete', 'hypotheses.destroy', ['hypothesis'], []],
            '○×を付ける' => ['put', 'fits.update', ['hypothesis', 'clue'], ['fit' => 'contradicts']],
            '○×を外す' => ['delete', 'fits.destroy', ['hypothesis', 'clue'], []],
            '予想を立てる' => ['post', 'predictions.store', ['hypothesis'], ['statement' => 'x', 'confidence' => 70, 'check_on' => '2099-01-01']],
            '答え合わせの画面' => ['get', 'predictions.show', ['prediction'], []],
            '答え合わせ' => ['post', 'predictions.resolve', ['prediction'], ['result' => 'hit']],
            '取り下げ' => ['post', 'predictions.withdraw', ['prediction'], []],
            'ひとことを閉じる' => ['post', 'hints.dismiss', ['case', 'rule'], []],
        ];
    }

    /** ほかの利用者が、持ち主の記録を開く・直す・消すと 403 になり、記録が変わらないことを、操作ごとに確かめる */
    #[DataProvider('strangerRequests')]
    public function test_strangers_are_forbidden(string $method, string $route, array $params, array $data): void
    {
        [, $case, $clue, $hypothesis, $prediction] = $this->board(true, ['title' => '持ち主のなぞ']);
        ClueHypothesis::create(['hypothesis_id' => $hypothesis->id, 'clue_id' => $clue->id, 'fit' => 'fits']);
        $records = ['case' => $case, 'clue' => $clue, 'hypothesis' => $hypothesis, 'prediction' => $prediction, 'rule' => 'single_hypothesis'];
        $before = [CaseFile::count(), Clue::count(), Hypothesis::count(), Prediction::count(), ClueHypothesis::count()];

        $response = $this->actingAs($this->member())->{$method}(route($route, array_map(fn ($p) => $records[$p], $params)), $data);

        $response->assertForbidden();
        $this->assertSame($before, [CaseFile::count(), Clue::count(), Hypothesis::count(), Prediction::count(), ClueHypothesis::count()]);
        $this->assertSame(['持ち主のなぞ', 'open', 'considering'], [$case->refresh()->title, $case->status->value, $hypothesis->refresh()->status->value]);
        $this->assertTrue($prediction->refresh()->isPending());
        $this->assertSame('fits', ClueHypothesis::first()->fit->value);
    }

    /** 一覧と今日の部品のための問い合わせには、他人の事件が出ないことを確かめる */
    public function test_lists_never_show_other_peoples_cases(): void
    {
        $this->board(true, ['title' => '他人のなぞ']);
        $me = $this->member();

        $this->actingAs($me)->get(route('cases.index'))->assertOk()->assertDontSee('他人のなぞ');
        $this->assertCount(0, app(OpenCasesQuery::class)->get($me));
    }

    /** 予想を立てるとき、他人の屋根裏の知識は選べず、事件の詳細の選択肢にも出ないことを確かめる */
    public function test_attic_options_are_only_mine(): void
    {
        [$user, $case] = $this->board();
        AtticNote::factory()->create(['rule' => '他人の一般則']);
        AtticNote::factory()->for($user)->create(['rule' => '自分の一般則']);

        $this->actingAs($user)->get(route('cases.show', $case))->assertSee('自分の一般則')->assertDontSee('他人の一般則');
    }

    /** ログインしていない人は、事件の画面を開けずトップへ送られることを確かめる */
    public function test_guests_are_redirected(): void
    {
        [, $case] = $this->board();

        $this->get(route('cases.index'))->assertRedirect(route('home'));
        $this->get(route('cases.show', $case))->assertRedirect(route('home'));
    }
}
