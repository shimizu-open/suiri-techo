<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WeeklyReflection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の週のふりかえりを作る（今週の月曜日）
 *
 * @extends Factory<WeeklyReflection>
 */
class WeeklyReflectionFactory extends Factory
{
    /** 週のふりかえりの既定の値を返す */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'week_start' => today()->startOfWeek()->toDateString(),
            'changed_case_file_id' => null,
            'abstraction' => '思い込みは、見たことと考えたことを分けると見つかる',
            'concrete' => '朝の忘れ物、仕事の段取り',
        ];
    }
}
