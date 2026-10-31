<?php

namespace Database\Factories;

use App\Models\SceneAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の名推理カードへの推理を作る
 *
 * @extends Factory<SceneAttempt>
 */
class SceneAttemptFactory extends Factory
{
    /** 推理の既定の値を返す */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scene_key' => 'sample-scene',
            'answer' => '日焼けの跡から、暑い国から帰ってきたと考えた',
        ];
    }
}
