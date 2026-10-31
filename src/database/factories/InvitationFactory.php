<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の招待を作る。used() で「登録に使われた」招待にする
 *
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /** 招待の既定の値を返す */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'note' => null,
            'used_at' => null,
        ];
    }

    /** 登録に使われた招待にする */
    public function used(): static
    {
        return $this->state(fn () => ['used_at' => now()]);
    }
}
