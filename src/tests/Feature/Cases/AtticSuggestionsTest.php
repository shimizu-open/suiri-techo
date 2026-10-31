<?php

namespace Tests\Feature\Cases;

use App\Models\AtticNote;
use App\Models\User;
use App\Queries\Cases\AtticSuggestionsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 屋根裏の棚からの提案（S-5）：本人の棚の、なぞ・手がかりと言葉が重なる知識だけを3件まで出す */
class AtticSuggestionsTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** その人の棚に知識を置く（どの事件からかも渡せる） */
    private function note(User $user, string $rule, ?int $caseId = null): AtticNote
    {
        return AtticNote::factory()->for($user)->create(['rule' => $rule, 'case_file_id' => $caseId]);
    }

    /** 重なる知識は「前にわかったこと：〜」として出て、見立ての書き出しと「使う知識」の選び先を持つことを確かめる */
    public function test_overlapping_notes_are_suggested(): void
    {
        [$user, $case] = $this->board(caseAttributes: ['title' => '体操着をなぜ忘れる？']);
        $note = $this->note($user, '体操着は前の晩に玄関へ置くと忘れにくい');

        $this->actingAs($user)->get(route('cases.show', $case))->assertOk()
            ->assertSee(config('suiri.suggest.attic.label').'体操着は前の晩に玄関へ置くと忘れにくい', false)
            ->assertSee('data-text="前にわかった『体操着は前の晩に玄関へ置くと忘れにくい』が当てはまるなら、"', false)
            ->assertSee('data-attic="'.$note->id.'"', false);
    }

    /** 言葉の重ならない知識・ほかの人の知識・この事件から生まれた知識は出ないことを確かめる */
    public function test_only_own_overlapping_notes_from_other_cases(): void
    {
        [$user, $case] = $this->board(caseAttributes: ['title' => '体操着をなぜ忘れる？']);
        $this->note($user, '電車は雨の日に遅れやすい');
        $this->note(User::factory()->create(), '体操着は前の晩に用意する');
        $this->note($user, '体操着は玄関に置く', $case->id);

        $this->assertSame([], app(AtticSuggestionsQuery::class)->for($case->load('clues'), $user));
        $this->actingAs($user)->get(route('cases.show', $case))->assertDontSee(config('suiri.suggest.attic.label'));
    }

    /** 重なる知識が多くても3件までで、重なりの多いものが先に選ばれることを確かめる */
    public function test_at_most_three_notes_ordered_by_overlap(): void
    {
        [$user, $case] = $this->board(caseAttributes: ['title' => '体操着をなぜ忘れる？']);
        $best = $this->note($user, '玄関に体操着の袋を置く');
        // 「体操着」だけが重なる知識を4つ置く（玄関・袋も重なる知識より後ろになる）
        foreach (range(1, 4) as $i) {
            $this->note($user, "体操着の決まり{$i}");
        }

        $ids = array_column(app(AtticSuggestionsQuery::class)->for($case->load('clues'), $user), 'id');

        $this->assertCount(3, $ids);
        $this->assertSame($best->id, $ids[0]);
    }

    /** 一度に読む件数より知識が多くても全部と比べ、古い知識でも重なりが多ければ選ばれ、重なりが同じなら新しいものが先になることを確かめる */
    public function test_all_notes_are_compared_beyond_one_chunk(): void
    {
        config(['suiri.suggest.attic.chunk' => 2]);
        [$user, $case] = $this->board(caseAttributes: ['title' => '体操着をなぜ忘れる？']);
        $best = $this->note($user, '玄関に体操着の袋を置く');
        // 「体操着」だけが重なる知識を4つ置く（いちばん古い $best は、新しい順に2件ずつ読む最初の何回かに入らない）
        $single = array_map(fn (int $i) => $this->note($user, "体操着の決まり{$i}")->id, range(1, 4));
        // いちばん新しい所に、重ならない知識を3つ置く
        foreach (range(1, 3) as $i) {
            $this->note($user, "電車は雨の日に遅れやすい{$i}");
        }

        $ids = array_column(app(AtticSuggestionsQuery::class)->for($case->load('clues'), $user), 'id');

        $this->assertSame([$best->id, $single[3], $single[2]], $ids);
    }

    /** 新しい順の選択肢の件数から外れた古い知識でも、提案したものは「使う知識」の選択肢に入ることを確かめる */
    public function test_suggested_note_is_selectable_even_if_old(): void
    {
        config(['suiri.cases.attic_options' => 1]);
        [$user, $case] = $this->board(caseAttributes: ['title' => '体操着をなぜ忘れる？']);
        $old = $this->note($user, '体操着は前の晩に用意すると忘れにくい');
        $this->note($user, '電車は雨の日に遅れやすい');

        $this->actingAs($user)->get(route('cases.show', $case))
            ->assertSee('<option value="'.$old->id.'"', false);
    }
}
