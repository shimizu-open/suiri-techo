<?php

namespace Database\Factories;

use App\Models\CaseFile;
use App\Models\Hypothesis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の見立てを作る。持ち主は事件の持ち主にそろえる。byPartner()・status('eliminated') で形を変える
 *
 * @extends Factory<Hypothesis>
 */
class HypothesisFactory extends Factory
{
    /** 見立ての既定の値を返す（自分が書いた・検討中） */
    public function definition(): array
    {
        return [
            'case_file_id' => CaseFile::factory(),
            'user_id' => fn (array $attributes) => CaseFile::find($attributes['case_file_id'])->user_id,
            'author' => 'self',
            'body' => fake()->randomElement(['時間割を見ていない', '袋が玄関から見えない所にある', '朝に急いでいる']),
            'status' => 'considering',
        ];
    }

    /** 相棒が書いた見立てにする */
    public function byPartner(): static
    {
        return $this->state(fn () => ['author' => 'partner']);
    }

    /** 状態を決める（considering・likely・eliminated・confirmed） */
    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
