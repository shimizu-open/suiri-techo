<?php

namespace Tests\Feature\Cases;

use App\Actions\Cases\ChangeHypothesisStatus;
use App\Actions\Cases\SetFit;
use App\Enums\Fit;
use App\Enums\HypothesisStatus;
use App\Models\AtticNote;
use App\Models\CalendarConnection;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/**
 * 事件の画面（views/cases/board。受付は CaseController@show）が、いまの段に合わせて「いまやること」のカードを1枚だけ出すことと、
 * 見直し・○× のカードから送ったあとも同じカードに戻ることを確かめる
 */
class CaseBoardStepsTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** その人として事件の画面を開いて返す（open・turn は ?open=・?turn=） */
    private function screen(User $user, CaseFile $case, ?string $open = null, ?string $turn = null): TestResponse
    {
        return $this->actingAs($user)->get(route('cases.show', array_filter(['case' => $case, 'open' => $open, 'turn' => $turn])))->assertOk();
    }

    /** いまやることのカード（step-card の枠）がいくつあるかを数える */
    private function cards(TestResponse $page): int
    {
        return substr_count($page->getContent(), 'overflow-hidden rounded-step');
    }

    /** 手がかりがない事件は「見たことを1つ教えて」のカード1枚だけで、送り先と入力の名前が今と同じことを確かめる */
    public function test_first_step_asks_for_one_clue_only(): void
    {
        $user = $this->member();
        $case = $this->caseOf($user, ['title' => '体操着をなぜ忘れる？']);
        $page = $this->screen($user, $case);

        $page->assertSee('事件 No.'.$case->id)->assertSee('体操着をなぜ忘れる？')->assertSee('ステップ 2 / 5')
            ->assertSee('見たことを1つ教えて')->assertSee('action="'.route('clues.store', $case).'"', false)
            ->assertSee('name="kind"', false)->assertSee('name="body"', false)->assertSee('これでOK')
            ->assertDontSee('どうしてだと思う？')->assertDontSee('name="statement"', false);
        $this->assertSame(1, $this->cards($page));
    }

    /** 手がかりがあれば、済んだ段に荷札の束が出て、カードは「どうしてだと思う？」に変わることを確かめる */
    public function test_after_a_clue_it_asks_why(): void
    {
        $user = $this->member();
        $case = $this->caseOf($user);
        Clue::factory()->create(['case_file_id' => $case->id, 'body' => '玄関に体操着の袋がなかった']);
        $page = $this->screen($user, $case);

        $page->assertSeeInOrder(['見たこと 1つ', '玄関に体操着の袋がなかった', 'ステップ 3 / 5', 'どうしてだと思う？'])
            ->assertSee('action="'.route('hypotheses.store', $case).'"', false)
            ->assertSee('見立て＝「どうしてだろう？」の答えの案', false)
            ->assertDontSee('見たことを1つ教えて');
        $this->assertSame(1, $this->cards($page));
    }

    /** 見たことが2つ以上で ○× がまだなら、先に ○× のカード（とばせる）を出し、印を付け終えると「もしそうなら」に進むことを確かめる */
    public function test_fit_card_comes_before_prediction_and_can_be_skipped(): void
    {
        [$user, $case, $clue, $hypothesis] = $this->board(false);
        $second = Clue::factory()->create(['case_file_id' => $case->id]);
        $page = $this->screen($user, $case);

        $page->assertSee('この見立てに合う？')->assertSee('id="hyp-'.$hypothesis->id.'"', false)
            ->assertSee('action="'.route('fits.update', [$hypothesis, $clue]).'"', false)
            ->assertSee('open=predict-'.$hypothesis->id, false)->assertSee('とばす');

        app(SetFit::class)->handle($hypothesis, $clue, Fit::Fits);
        app(SetFit::class)->handle($hypothesis->refresh(), $second, Fit::Fits);
        $this->screen($user, $case)->assertSee('もしそうなら、何が見える？')->assertDontSee('この見立てに合う？');
    }

    /** 「もしそうなら」のカードは、文・確かさ（50〜95％の大きな選択）・確かめる日（候補つき）を1つのフォームで今と同じ送り先へ送ることを確かめる */
    public function test_prediction_card_uses_the_same_form(): void
    {
        [$user, $case, , $hypothesis] = $this->board(false);
        $page = $this->screen($user, $case, 'predict-'.$hypothesis->id);

        $page->assertSee('action="'.route('predictions.store', $hypothesis).'"', false)
            ->assertSee('name="statement"', false)->assertSee('どのくらい自信ある？')
            ->assertSee('name="confidence" value="50"', false)->assertSee('name="confidence" value="95"', false)
            ->assertDontSee('name="confidence" value="100"', false)
            ->assertSee('name="check_on"', false)->assertSee('明日')->assertSee('1週間後')
            ->assertSee('この文と自信は直せないよ');
        $this->assertSame(1, $this->cards($page));
    }

    /** 答え合わせを待つ間は大きな日付と「〇月〇日に確かめよう」を出し、カレンダーとつないでいる（切れていない）ときだけ「カレンダーに入れたよ」と言うことを確かめる */
    public function test_waiting_card_shows_the_date(): void
    {
        [$user, $case, , $hypothesis] = $this->board(false);
        $prediction = Prediction::factory()->create(['hypothesis_id' => $hypothesis->id, 'check_on' => today()->addDays(3)->toDateString()]);
        $date = Carbon::parse($prediction->check_on)->isoFormat('M月D日（ddd）');

        $this->screen($user, $case)->assertSee($date.'に確かめよう')->assertSee('やることに入れたよ')
            ->assertSee('action="'.route('predictions.withdraw', $prediction).'"', false);
        // つないだ・切れたあとは、読み込み直した利用者で開く（ログイン中の利用者は毎回読み直されるため）
        $connection = CalendarConnection::factory()->for($user)->create();
        $this->screen($user->fresh(), $case)->assertSee('カレンダーに入れたよ')->assertDontSee('やることに入れたよ');
        $connection->update(['status' => 'broken']);
        $this->screen($user->fresh(), $case)->assertSee('やることに入れたよ');
    }

    /** 確かめる日が来たら「答え合わせをしよう」と前に書いた予想を出し、答え合わせの画面へ進むことを確かめる */
    public function test_due_prediction_asks_to_resolve(): void
    {
        [$user, $case, , , $prediction] = $this->board(true);

        $this->screen($user, $case)->assertSee('答え合わせをしよう')->assertSee($prediction->statement)
            ->assertSee('href="'.route('predictions.show', $prediction).'"', false)->assertSee('ステップ 5 / 5');
    }

    /** 当たりの見立てがあれば「事件を閉じる？」（解決！・おあずけ）を出し、閉じたあとは大きなはんこと「つまり、何がわかった？」を出すことを確かめる */
    public function test_close_and_closed_cards(): void
    {
        [$user, $case, , $hypothesis] = $this->board(false);
        app(ChangeHypothesisStatus::class)->handle($hypothesis, HypothesisStatus::Confirmed);

        $this->screen($user, $case)->assertSee('事件を閉じる？')->assertSee('解決！')->assertSee('おあずけ')
            ->assertSee('action="'.route('cases.close', $case).'"', false);

        $case->update(['status' => 'solved', 'closed_at' => now(), 'conclusion' => '前の晩に見ると忘れない']);
        $this->screen($user, $case)->assertSeeInOrder(['解決！', '前の晩に見ると忘れない', 'つまり、何がわかった？'])
            ->assertSee('action="'.route('attic.store').'"', false)->assertSee('name="case_file_id" value="'.$case->id.'"', false)
            ->assertSee('action="'.route('cases.reopen', $case).'"', false);
    }

    /** ワトソンのひとことはカードの中に1つだけ（閉じるボタンつき）出て、そのときは言葉の説明の吹き出しを出さないことを確かめる */
    public function test_watson_note_sits_inside_the_card(): void
    {
        [$user, $case] = $this->board(false);

        $this->screen($user, $case, 'predict-'.$case->hypotheses()->first()->id)
            ->assertSeeInOrder(['もしそうなら、何が見える？', 'ワトソンのひとこと', 'ほかの説明は？'])
            ->assertSee('action="'.route('hints.dismiss', [$case, 'single_hypothesis']).'"', false)
            ->assertDontSee('もしそうなら（予想）＝');
    }

    /** 「ほかにできること」から来たときは、その用事（なぞを直す・見たことを直す・見立てを見直す）がカードになることを確かめる */
    public function test_open_turns_a_side_task_into_the_card(): void
    {
        [$user, $case, $clue, $hypothesis] = $this->board(false);

        $this->screen($user, $case)->assertSee('ほかにできること')->assertSee('open=add-clue', false)->assertSee('open=edit-case', false);
        $this->screen($user, $case, 'edit-case')->assertSee('なぞを直す')->assertSee('action="'.route('cases.update', $case).'"', false);
        $this->screen($user, $case, 'edit-clue-'.$clue->id)->assertSee('見たことを直す')
            ->assertSee('action="'.route('clues.update', $clue).'"', false);
        $this->screen($user, $case, 'edit-hyps')->assertSee('見立てを見直そう')
            ->assertSee('action="'.route('hypotheses.update', $hypothesis).'"', false)->assertSee('open=fit-'.$hypothesis->id, false);
    }

    /** 相棒モードで見せ合う前は、いまの番の人の見立てだけで、もう片方の見立ての文も数も画面に出さないことを確かめる */
    public function test_partner_turn_hides_the_other_side(): void
    {
        $user = $this->member(['partner_name' => '長男']);
        $case = $this->caseOf($user, ['partner_mode' => true]);
        Clue::factory()->create(['case_file_id' => $case->id]);
        Hypothesis::factory()->create(['case_file_id' => $case->id, 'author' => 'partner', 'body' => '長男の考え']);
        Hypothesis::factory()->create(['case_file_id' => $case->id, 'author' => 'self', 'body' => '父の考え']);

        $this->screen($user, $case, null, 'partner')->assertSee('長男の考え')->assertDontSee('父の考え')->assertDontSee('かくれているよ');
        $this->screen($user, $case, null, 'self')->assertSee('父の考え')->assertDontSee('長男の考え');
    }

    /** ふだんの場面が親子の人には、子ども向けの言葉（「かも」・見に行く）を出すことを確かめる */
    public function test_kid_words_for_family_users(): void
    {
        [$user, $case, , $hypothesis] = $this->board(false);
        $user->forceFill(['default_scene' => 'family'])->save();

        $this->screen($user, $case, 'predict-'.$hypothesis->id)->assertSee('「かも」')->assertSee('この日に見に行く')->assertSee('たぶん そう');
    }

    /** 閉じた事件からもう屋根裏の棚にしまったことがあれば、「つまり、何がわかった？」を聞き直さず、しまったことを見せることを確かめる */
    public function test_closed_case_shows_what_was_shelved(): void
    {
        [$user, $case] = $this->board(false);
        $case->update(['status' => 'solved', 'closed_at' => now()]);
        AtticNote::factory()->for($user)->create(['case_file_id' => $case->id, 'rule' => '前の晩に準備すると忘れにくい']);

        $this->screen($user, $case)->assertSee('屋根裏の棚にしまったこと')->assertSee('前の晩に準備すると忘れにくい')
            ->assertDontSee('つまり、何がわかった？')->assertDontSee('action="'.route('attic.store').'"', false);
    }

    /** 見直し・○× のカードから送ると（隠し項目 back_open）、同じカードに戻り、決まった候補でない値は付けずに戻すことを確かめる */
    public function test_forms_return_to_the_same_card(): void
    {
        [$user, $case, $clue, $hypothesis] = $this->board(false);
        $this->actingAs($user);

        $this->patch(route('hypotheses.update', $hypothesis), ['status' => 'likely', 'back_open' => 'edit-hyps'])
            ->assertRedirect(route('cases.show', ['case' => $case, 'open' => 'edit-hyps']).'#hyp-'.$hypothesis->id);
        $this->put(route('fits.update', [$hypothesis, $clue]), ['fit' => 'fits', 'back_open' => 'fit-'.$hypothesis->id])
            ->assertRedirect(route('cases.show', ['case' => $case, 'open' => 'fit-'.$hypothesis->id]).'#hyp-'.$hypothesis->id);
        // 候補にない値・形のちがう値は、?open= を付けずにふだんの事件の画面へ戻す
        foreach (['https://example.com', 'fit-abc', 'edit-hyps-1', 'predict-'] as $bad) {
            $this->put(route('fits.update', [$hypothesis, $clue]), ['fit' => 'fits', 'back_open' => $bad])
                ->assertRedirect(route('cases.show', $case).'#hyp-'.$hypothesis->id);
        }
    }
}
