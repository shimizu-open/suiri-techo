<?php

namespace Database\Factories;

use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の事件を作る。ふつうは「捜査中・日常の場面・きっかけなし」。
 * solved()・cold()・partner()・scene('work') で形を変える
 *
 * @extends Factory<CaseFile>
 */
class CaseFileFactory extends Factory
{
    /** 事件の既定の値を返す */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement(['体操着をなぜ忘れる？', '朝の電車はなぜ混む？', '植木の葉はなぜ黄色い？']),
            'scene' => 'daily',
            'status' => 'open',
            'partner_mode' => false,
        ];
    }

    /** 解決した事件にする */
    public function solved(): static
    {
        return $this->state(fn () => ['status' => 'solved', 'closed_at' => now(), 'conclusion' => '前の晩に用意すれば忘れない']);
    }

    /** 迷宮入りした事件にする */
    public function cold(): static
    {
        return $this->state(fn () => ['status' => 'cold', 'closed_at' => now()]);
    }

    /** 相棒モードの事件にする（まだ見せ合っていない） */
    public function partner(): static
    {
        return $this->state(fn () => ['partner_mode' => true, 'revealed_at' => null]);
    }

    /** 場面を決める（daily・family・work） */
    public function scene(string $scene): static
    {
        return $this->state(fn () => ['scene' => $scene]);
    }
}
