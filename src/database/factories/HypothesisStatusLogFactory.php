<?php

namespace Database\Factories;

use App\Models\Hypothesis;
use App\Models\HypothesisStatusLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の「見立ての状態を変えた記録」を作る。持ち主は見立ての持ち主にそろえる
 *
 * @extends Factory<HypothesisStatusLog>
 */
class HypothesisStatusLogFactory extends Factory
{
    /** 記録の既定の値を返す（検討中 → 有力） */
    public function definition(): array
    {
        return [
            'hypothesis_id' => Hypothesis::factory(),
            'user_id' => fn (array $attributes) => Hypothesis::find($attributes['hypothesis_id'])->user_id,
            'from_status' => 'considering',
            'to_status' => 'likely',
        ];
    }
}
