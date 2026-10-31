<?php

namespace Tests\Feature\Review;

use App\Models\AtticNote;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 屋根裏の棚（/attic）：一覧・足す・直す・消す。他人の知識は見えず、触れない */
class AtticNoteTest extends TestCase
{
    use RefreshDatabase;

    /** 一覧には自分の知識だけが、生まれた事件と、使った予想の数と一緒に出ることを確かめる */
    public function test_index_lists_own_notes_with_case_and_usage(): void
    {
        $user = User::factory()->create();
        $case = CaseFile::factory()->for($user)->create(['title' => '体操着をなぜ忘れる？']);
        $note = AtticNote::factory()->for($user)->create(['rule' => '前の晩に用意すると忘れない', 'case_file_id' => $case->id]);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id]);
        Prediction::factory()->count(2)->create(['hypothesis_id' => $hypothesis->id, 'attic_note_id' => $note->id]);
        AtticNote::factory()->create(['rule' => '他人の知識']);

        $this->actingAs($user)->get(route('attic.index'))->assertOk()
            ->assertSee('前の晩に用意すると忘れない')
            ->assertSee('体操着をなぜ忘れる？')
            ->assertSeeInOrder(['使った予想', '2', '件'])
            ->assertSee('緋色の研究')
            ->assertDontSee('他人の知識');
    }

    /** 知識を足すと、自分の知識として保存され、空のたとえば？は null になることを確かめる */
    public function test_store_creates_a_note(): void
    {
        $user = User::factory()->create();
        $case = CaseFile::factory()->for($user)->create();

        $this->actingAs($user)->post(route('attic.store'), [
            'rule' => ' 急ぐと見落とす ', 'examples' => '', 'case_file_id' => $case->id,
        ])->assertRedirect(route('attic.index'))->assertSessionHas('status');

        $note = AtticNote::sole();
        $this->assertSame($user->id, $note->user_id);
        $this->assertSame('急ぐと見落とす', $note->rule);
        $this->assertNull($note->examples);
        $this->assertSame($case->id, $note->case_file_id);
    }

    /** つまり？が空・長すぎる、他人の事件を選んだ、のときは保存されないことを確かめる */
    public function test_store_rejects_invalid_input(): void
    {
        $user = User::factory()->create();
        $others = CaseFile::factory()->create();

        $this->actingAs($user)->post(route('attic.store'), ['rule' => ' '])->assertSessionHasErrors('rule');
        $this->actingAs($user)->post(route('attic.store'), ['rule' => str_repeat('あ', config('suiri.limits.attic_rule') + 1)])
            ->assertSessionHasErrors('rule');
        $this->actingAs($user)->post(route('attic.store'), ['rule' => 'ok', 'case_file_id' => $others->id])
            ->assertSessionHasErrors('case_file_id');

        $this->assertSame(0, AtticNote::count());
    }

    /** 自分の知識を直せて、直す画面（?edit=）ではその1枚が入力欄になり、足す欄は隠れることを確かめる */
    public function test_owner_can_update(): void
    {
        $user = User::factory()->create();
        $note = AtticNote::factory()->for($user)->create(['rule' => '古い決まり']);

        $this->actingAs($user)->get(route('attic.index', ['edit' => $note->id]))->assertOk()
            ->assertSee(route('attic.update', $note), false)
            ->assertDontSee(config('suiri.review.attic.add'));

        $this->actingAs($user)->patch(route('attic.update', $note), ['rule' => '新しい決まり', 'examples' => '朝の支度'])
            ->assertRedirect(route('attic.index'));

        $this->assertSame('新しい決まり', $note->refresh()->rule);
        $this->assertSame('朝の支度', $note->examples);
    }

    /** 自分の知識を消すと、その知識を使った予想は残り、つながりだけが外れることを確かめる */
    public function test_owner_can_delete_and_predictions_remain(): void
    {
        $user = User::factory()->create();
        $note = AtticNote::factory()->for($user)->create();
        $case = CaseFile::factory()->for($user)->create();
        $prediction = Prediction::factory()->create([
            'hypothesis_id' => Hypothesis::factory()->create(['case_file_id' => $case->id])->id,
            'attic_note_id' => $note->id,
        ]);

        $this->actingAs($user)->delete(route('attic.destroy', $note))->assertRedirect(route('attic.index'));

        $this->assertModelMissing($note);
        $this->assertNull($prediction->refresh()->attic_note_id);
    }

    /** 他人の知識は直すことも消すこともできない（403）ことを確かめる */
    public function test_cannot_touch_someone_elses_note(): void
    {
        $user = User::factory()->create();
        $theirs = AtticNote::factory()->create(['rule' => '他人の決まり']);

        $this->actingAs($user)->patch(route('attic.update', $theirs), ['rule' => '書き換え'])->assertForbidden();
        $this->actingAs($user)->delete(route('attic.destroy', $theirs))->assertForbidden();

        $this->assertSame('他人の決まり', $theirs->refresh()->rule);
    }

    /** 何もないときは絵と一言と「自分で書く」ボタン（?add=1）が出て、押すと書くカードが出ること・親子の人には「ぼくの図鑑・わたしの図鑑」が出ることを確かめる */
    public function test_empty_state_and_kid_name(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('attic.index'))->assertOk()
            ->assertSee(config('suiri.review.attic.empty.title'))
            ->assertSee(route('attic.index', ['add' => 1]), false)
            ->assertDontSee('action="'.route('attic.store').'"', false)
            ->assertDontSee('ぼくの図鑑');
        $this->actingAs($user)->get(route('attic.index', ['add' => 1]))->assertOk()
            ->assertSee(config('suiri.review.attic.add_title'))
            ->assertSee('action="'.route('attic.store').'"', false);

        $this->actingAs(User::factory()->create(['default_scene' => 'family']))->get(route('attic.index'))->assertOk()
            ->assertSee('ぼくの図鑑・わたしの図鑑');
    }
}
