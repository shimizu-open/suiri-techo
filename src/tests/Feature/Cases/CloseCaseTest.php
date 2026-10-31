<?php

namespace Tests\Feature\Cases;

use App\Models\AtticNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Cases\Concerns\BuildsCases;
use Tests\TestCase;

/** 事件を閉じる・開き直す（C-9）。「つまり？」があれば屋根裏の棚に入る */
class CloseCaseTest extends TestCase
{
    use BuildsCases, RefreshDatabase;

    /** 解決で閉じると、結論と閉じた日時が残り、「つまり？・たとえば？」が屋根裏の棚に入り、事件の画面では聞き直さずにしまったことを見せることを確かめる */
    public function test_close_as_solved_with_an_attic_note(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->post(route('cases.close', $case), [
            'status' => 'solved', 'conclusion' => '前の晩に用意すると忘れない',
            'rule' => '準備は前の晩に', 'examples' => '遠足の水筒',
        ])->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame('solved', $case->status->value);
        $this->assertNotNull($case->closed_at);
        $note = AtticNote::firstOrFail();
        $this->assertSame([$user->id, $case->id, '準備は前の晩に', '遠足の水筒'], [$note->user_id, $note->case_file_id, $note->rule, $note->examples]);
        $this->actingAs($user)->get(route('cases.show', $case))->assertSee('に閉じたよ')->assertSee('事件を開き直す')
            ->assertSee('準備は前の晩に')->assertDontSee('つまり、何がわかった？');
    }

    /** 迷宮入りは結論なしでも閉じられ、棚には何も入らないこと、「たとえば？」だけでは断られることを確かめる */
    public function test_close_as_cold_without_notes(): void
    {
        [$user, $case] = $this->board();

        $this->actingAs($user)->post(route('cases.close', $case), ['status' => 'cold', 'examples' => '例だけ'])
            ->assertSessionHasErrors('rule');
        $this->actingAs($user)->post(route('cases.close', $case), ['status' => 'cold'])->assertRedirect();

        $this->assertSame('cold', $case->refresh()->status->value);
        $this->assertSame(0, AtticNote::count());
    }

    /** 開き直すと捜査中に戻り、閉じた事件にはもう一度閉じる・見立てを足すことができないことを確かめる */
    public function test_reopen_and_closed_case_rules(): void
    {
        [$user, $case] = $this->board(false, ['status' => 'solved', 'closed_at' => now()]);

        $this->actingAs($user)->post(route('cases.close', $case), ['status' => 'cold'])->assertForbidden();
        $this->actingAs($user)->post(route('hypotheses.store', $case), ['body' => 'あとから'])->assertForbidden();

        $this->actingAs($user)->delete(route('cases.reopen', $case))->assertRedirect();
        $this->assertTrue($case->refresh()->isOpen());
        $this->assertNull($case->closed_at);
    }
}
