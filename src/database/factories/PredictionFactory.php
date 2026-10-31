<?php

namespace Database\Factories;

use App\Models\Hypothesis;
use App\Models\Prediction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の予想を作る。事件と持ち主は見立てにそろえる。ふつうは「明日確かめる・確かさ70・答え合わせ待ち」。
 * due()・resolved('miss', 'assumption')・withdrawn() で形を変える
 *
 * @extends Factory<Prediction>
 */
class PredictionFactory extends Factory
{
    /** 予想の既定の値を返す */
    public function definition(): array
    {
        return [
            'hypothesis_id' => Hypothesis::factory(),
            'case_file_id' => fn (array $attributes) => Hypothesis::find($attributes['hypothesis_id'])->case_file_id,
            'user_id' => fn (array $attributes) => Hypothesis::find($attributes['hypothesis_id'])->user_id,
            'statement' => '前の晩に時間割を見れば、体操着を忘れないはず',
            'confidence' => 70,
            'check_on' => today()->addDay()->toDateString(),
            'check_at' => null,
        ];
    }

    /** 確かめる日が今日の予想にする（答え合わせ待ちに出る） */
    public function due(): static
    {
        return $this->state(fn () => ['check_on' => today()->toDateString()]);
    }

    /** 答え合わせ済みにする（結果と、外れなら原因） */
    public function resolved(string $result = 'hit', ?string $missReason = null): static
    {
        return $this->state(fn () => ['result' => $result, 'miss_reason' => $missReason, 'resolved_at' => now()]);
    }

    /** 取り下げた予想にする */
    public function withdrawn(): static
    {
        return $this->state(fn () => ['withdrawn_at' => now()]);
    }
}
