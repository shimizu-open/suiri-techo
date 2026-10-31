<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * テスト用の利用者を作る。ふつうは「同意済み・場面は日常」。
 * unagreed() で同意前の人、withPartner() で相棒の名前がある人にする
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** 利用者の既定の値を返す（Google の ID とメールは毎回ちがうもの） */
    public function definition(): array
    {
        return [
            'google_id' => 'g-'.Str::random(20),
            'name' => fake()->randomElement(['ワトソン', 'ハドソン', 'レストレード', 'メアリー']),
            'email' => fake()->unique()->safeEmail(),
            'avatar_url' => null,
            'partner_name' => null,
            'default_scene' => 'daily',
            'cue' => null,
            'agreed_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /** まだ利用規約などに同意していない人にする */
    public function unagreed(): static
    {
        return $this->state(fn () => ['agreed_at' => null]);
    }

    /** 相棒の名前がある人にする（相棒モードを使える） */
    public function withPartner(string $name = '長男'): static
    {
        return $this->state(fn () => ['partner_name' => $name]);
    }
}
