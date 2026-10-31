<?php

namespace Database\Factories;

use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用のタスクを作る。ふつうは「今日が期日・終日・ふつうの種類・まだ済んでいない」。
 * done()・someday()・at('09:00')・verify($prediction) で形を変える
 *
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /** タスクの既定の値を返す */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement(['体操着を用意する', '牛乳を買う', '図書館に本を返す', '週報を書く']),
            'memo' => null,
            'due_on' => today()->toDateString(),
            'starts_at' => null,
            'duration_minutes' => null,
            'actual_minutes' => null,
            'kind' => 'normal',
            'done_at' => null,
        ];
    }

    /** 済んだタスクにする */
    public function done(): static
    {
        return $this->state(fn () => ['done_at' => now()]);
    }

    /** 期日のない「いつか」のタスクにする */
    public function someday(): static
    {
        return $this->state(fn () => ['due_on' => null]);
    }

    /** 開始時刻と長さ（分）のあるタスクにする */
    public function at(string $time, int $minutes = 30): static
    {
        return $this->state(fn () => ['starts_at' => $time, 'duration_minutes' => $minutes]);
    }

    /** 予想から作った「確かめる」タスクにする（持ち主・期日は予想に合わせる） */
    public function verify(Prediction $prediction): static
    {
        return $this->state(fn () => [
            'user_id' => $prediction->user_id,
            'kind' => 'verify',
            'prediction_id' => $prediction->id,
            'title' => '確かめる：'.$prediction->statement,
            'due_on' => $prediction->check_on,
        ]);
    }
}
