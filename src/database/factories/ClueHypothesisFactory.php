<?php

namespace Database\Factories;

use App\Models\Clue;
use App\Models\ClueHypothesis;
use App\Models\Hypothesis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の○×を作る。手がかりは見立てと同じ事件のものを作る。contradicts() で × にする
 *
 * @extends Factory<ClueHypothesis>
 */
class ClueHypothesisFactory extends Factory
{
    /** ○×の既定の値を返す（○ 合う） */
    public function definition(): array
    {
        return [
            'hypothesis_id' => Hypothesis::factory(),
            'clue_id' => fn (array $attributes) => Clue::factory()->create([
                'case_file_id' => Hypothesis::find($attributes['hypothesis_id'])->case_file_id,
            ])->id,
            'fit' => 'fits',
        ];
    }

    /** × 合わない にする */
    public function contradicts(): static
    {
        return $this->state(fn () => ['fit' => 'contradicts']);
    }
}
