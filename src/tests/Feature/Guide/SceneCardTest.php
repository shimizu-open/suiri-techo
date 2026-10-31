<?php

namespace Tests\Feature\Guide;

use App\Models\SceneAttempt;
use App\Models\User;
use App\Support\Guide\GuideLibrary;
use App\Support\Guide\ReasoningSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 名推理カード：表には手がかりだけ、裏返すとホームズの推理。自分の推理の保存（上書き）と、他人の推理が見えないこと */
class SceneCardTest extends TestCase
{
    use RefreshDatabase;

    /** どのカードも、表の HTML にはホームズの推理・確かめ方・引用が入っておらず、裏には入っていることを確かめる */
    public function test_reasoning_is_hidden_until_the_card_is_turned_over(): void
    {
        $user = User::factory()->create();

        // カードを1枚ずつ、表と裏を開いて比べる
        foreach (app(GuideLibrary::class)->scenes() as $scene) {
            $front = $this->actingAs($user)->get(route('guide.scenes.show', $scene['key']))->assertOk()->assertSee($scene['front']);
            $back = $this->actingAs($user)->get(route('guide.scenes.show', ['key' => $scene['key'], 'side' => 'back']))->assertOk();
            $first = mb_substr(ReasoningSteps::split($scene['reasoning'])[0]['text'], 0, 15);

            $front->assertDontSee($first)->assertDontSee($scene['verification'])->assertDontSee('ホームズはこう考えた');
            $back->assertSee($first)->assertSee($scene['verification']);
            // 英語の引用があるカードは、引用も裏にだけ出ることを確かめる
            if ($scene['quote_en']) {
                $front->assertDontSee($scene['quote_en']);
                $back->assertSee($scene['quote_en']);
            }
        }
    }

    /** ホームズが外した場面のカードの裏にだけ、「名探偵も外す」のひとことが出ることを確かめる */
    public function test_mistake_cards_show_the_note_on_the_back(): void
    {
        $user = User::factory()->create();
        $note = app(GuideLibrary::class)->overview()['mistake_note'];

        $this->actingAs($user)->get(route('guide.scenes.show', ['key' => 'yellow-face-norbury', 'side' => 'back']))->assertSee($note);
        $this->actingAs($user)->get(route('guide.scenes.show', ['key' => 'scandal-17-steps', 'side' => 'back']))->assertDontSee($note);
    }

    /** 推理を書いて裏返すと scene_attempts に保存され、裏で自分の推理とホームズの推理が並ぶことを確かめる */
    public function test_writing_an_answer_saves_it_and_turns_the_card_over(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('guide.scenes.attempt', 'scandal-17-steps'), ['answer' => '  毎日通っても数えていない  '])
            ->assertRedirect(route('guide.scenes.show', ['key' => 'scandal-17-steps', 'side' => 'back']));

        $this->assertDatabaseHas('scene_attempts', ['user_id' => $user->id, 'scene_key' => 'scandal-17-steps', 'answer' => '毎日通っても数えていない']);
        $this->actingAs($user)->get(route('guide.scenes.show', ['key' => 'scandal-17-steps', 'side' => 'back']))
            ->assertSee('毎日通っても数えていない')->assertSee('ホームズはこう考えた');
    }

    /** 同じカードに書き直すと、行は増えずに上書きされ、表の欄には前に書いた推理が入っていることを確かめる */
    public function test_writing_again_overwrites_the_answer(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('guide.scenes.attempt', 'sign-watch'), ['answer' => '最初の推理']);
        $this->actingAs($user)->post(route('guide.scenes.attempt', 'sign-watch'), ['answer' => '直した推理']);

        $this->assertSame(1, SceneAttempt::where('user_id', $user->id)->count());
        $this->assertSame('直した推理', SceneAttempt::where('user_id', $user->id)->value('answer'));
        $this->actingAs($user)->get(route('guide.scenes.show', 'sign-watch'))->assertSee('直した推理')->assertDontSee('最初の推理');
    }

    /** 何も書かずに送ると保存されず、前に書いた推理も消えずに、裏へ進むことを確かめる */
    public function test_an_empty_answer_turns_the_card_without_saving(): void
    {
        $user = User::factory()->create();
        $back = route('guide.scenes.show', ['key' => 'sign-watch', 'side' => 'back']);

        $this->actingAs($user)->post(route('guide.scenes.attempt', 'sign-watch'), ['answer' => '   '])->assertRedirect($back);
        $this->assertDatabaseCount('scene_attempts', 0);

        $this->actingAs($user)->post(route('guide.scenes.attempt', 'sign-watch'), ['answer' => '残る推理']);
        $this->actingAs($user)->post(route('guide.scenes.attempt', 'sign-watch'), ['answer' => ''])->assertRedirect($back);
        $this->assertSame('残る推理', SceneAttempt::where('user_id', $user->id)->value('answer'));
    }

    /** 推理が長すぎると保存されず、エラーで戻されることを確かめる */
    public function test_a_too_long_answer_is_rejected(): void
    {
        $user = User::factory()->create();
        $tooLong = str_repeat('あ', config('suiri.limits.scene_answer') + 1);

        $this->actingAs($user)->from(route('guide.scenes.show', 'sign-watch'))
            ->post(route('guide.scenes.attempt', 'sign-watch'), ['answer' => $tooLong])
            ->assertRedirect(route('guide.scenes.show', 'sign-watch'))->assertSessionHasErrors('answer');
        $this->assertDatabaseCount('scene_attempts', 0);
    }

    /** ほかの人が書いた推理は、カードの表にも裏にも一覧の印にも出ず、書き直しても他人の行は変わらないことを確かめる */
    public function test_other_users_answers_are_never_shown(): void
    {
        $other = User::factory()->create();
        SceneAttempt::factory()->for($other)->create(['scene_key' => 'sign-watch', 'answer' => 'ほかの人のひみつの推理']);
        $me = User::factory()->create();

        $this->actingAs($me)->get(route('guide.scenes.show', 'sign-watch'))->assertDontSee('ほかの人のひみつの推理');
        $this->actingAs($me)->get(route('guide.scenes.show', ['key' => 'sign-watch', 'side' => 'back']))
            ->assertDontSee('ほかの人のひみつの推理')->assertSee('書かずに裏返したね');
        $this->actingAs($me)->get(route('guide.scenes.index'))->assertDontSee('推理を書いた');

        $this->actingAs($me)->post(route('guide.scenes.attempt', 'sign-watch'), ['answer' => '自分の推理']);
        $this->assertSame('ほかの人のひみつの推理', SceneAttempt::where('user_id', $other->id)->value('answer'));
        $this->assertSame('自分の推理', SceneAttempt::where('user_id', $me->id)->value('answer'));
    }

    /** 推理を書いたカードには、一覧で「推理を書いた」の印が付くことを確かめる */
    public function test_scene_list_marks_cards_with_my_answer(): void
    {
        $user = User::factory()->create();
        SceneAttempt::factory()->for($user)->create(['scene_key' => 'sign-watch']);

        $this->actingAs($user)->get(route('guide.scenes.index'))->assertSee('推理を書いた');
    }
}
