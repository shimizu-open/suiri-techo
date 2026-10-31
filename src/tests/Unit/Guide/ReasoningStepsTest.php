<?php

namespace Tests\Unit\Guide;

use App\Support\Guide\ReasoningSteps;
use Tests\TestCase;

/** ホームズの推理の文を「事実・推理・結論」の段に分ける係（ReasoningSteps） */
class ReasoningStepsTest extends TestCase
{
    /** 目印のある文は、事実・推理・結論の3段に分かれ、目印は見出しになることを確かめる */
    public function test_it_splits_by_the_marks(): void
    {
        $steps = ReasoningSteps::split('事実：靴に赤土がある。推理：郵便局に寄った。結論：電報を打った。ただし確かめが要る。');

        $this->assertSame([
            ['label' => '事実', 'text' => '靴に赤土がある。'],
            ['label' => '推理', 'text' => '郵便局に寄った。'],
            ['label' => '結論', 'text' => '電報を打った。ただし確かめが要る。'],
        ], $steps);
    }

    /** 目印がない文は、見出しなしの1段になることを確かめる */
    public function test_text_without_marks_becomes_one_step(): void
    {
        $this->assertSame([['label' => null, 'text' => '目印のない推理。']], ReasoningSteps::split('目印のない推理。'));
    }
}
