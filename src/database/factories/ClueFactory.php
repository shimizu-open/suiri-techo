<?php

namespace Database\Factories;

use App\Models\CaseFile;
use App\Models\Clue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の手がかりを作る。持ち主（user_id）は事件の持ち主にそろえる。kind('absent') で種類を変える
 *
 * @extends Factory<Clue>
 */
class ClueFactory extends Factory
{
    /** 手がかりの既定の値を返す（種類は「見た」） */
    public function definition(): array
    {
        return [
            'case_file_id' => CaseFile::factory(),
            'user_id' => fn (array $attributes) => CaseFile::find($attributes['case_file_id'])->user_id,
            'kind' => 'seen',
            'body' => fake()->randomElement(['玄関に体操着の袋がなかった', '朝7時に起きた', '「明日は体育がない」と言った']),
        ];
    }

    /** 種類を決める（seen・heard・absent） */
    public function kind(string $kind): static
    {
        return $this->state(fn () => ['kind' => $kind]);
    }
}
