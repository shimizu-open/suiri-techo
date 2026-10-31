<?php

namespace Database\Factories;

use App\Models\CaseFile;
use App\Models\DismissedHint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の「閉じたワトソンのひとこと」を作る。持ち主は事件の持ち主にそろえる
 *
 * @extends Factory<DismissedHint>
 */
class DismissedHintFactory extends Factory
{
    /** 閉じたひとことの既定の値を返す */
    public function definition(): array
    {
        return [
            'case_file_id' => CaseFile::factory(),
            'user_id' => fn (array $attributes) => CaseFile::find($attributes['case_file_id'])->user_id,
            'rule' => 'single_hypothesis',
        ];
    }
}
